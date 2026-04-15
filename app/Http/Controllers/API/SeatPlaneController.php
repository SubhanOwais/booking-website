<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Discount;
use App\Models\CompanyCity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SeatPlaneController extends Controller
{
    // =========================================================================
    // Hardcoded API configs — operator_id must match companies.company_id in DB
    // name and logo removed — fetched from DB instead
    // =========================================================================
    private $companies = [];

    public function __construct()
    {
        $this->companies = array_values(config('companies.bus', []));
    }

    // =========================================================================
    // ✅ Sanitize exception messages before sending to client
    // Strips URLs, IPs, ports, credentials from any error string
    // =========================================================================
    private function sanitizeError(\Exception $e, string $context = ''): string
    {
        // Log the REAL error with full details server-side only
        Log::error("SeatPlaneController error" . ($context ? " [{$context}]" : ''), [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        $message = $e->getMessage();

        // Detect connection/timeout errors and return a clean message
        if (
            str_contains($message, 'cURL error') ||
            str_contains($message, 'Failed to connect') ||
            str_contains($message, 'Timeout was reached') ||
            str_contains($message, 'Connection refused') ||
            str_contains($message, 'Could not resolve host')
        ) {
            return 'Unable to reach the booking service. Please try again later.';
        }

        // For any other exception, return a generic message — never expose internals
        return 'An unexpected error occurred. Please try again.';
    }

    // =========================================================================
    // ✅ PRIMARY lookup — by operator_id matching companies.company_id in DB
    // Used by index(), holdSeats(), unholdSeats(), getSourceTerminals()
    // Payload sends operator_id — we match it to DB company_id
    // =========================================================================
    private function findCompanyByOperatorId(string $operatorId): ?array
    {
        // Find matching hardcoded config
        $config = collect($this->companies)
            ->firstWhere('operator_id', (string) $operatorId);

        if (!$config) return null;

        // Load DB record where company_id == operator_id
        $dbCompany = Company::where('company_id', (int) $operatorId)
            ->where('is_active', true)
            ->first(['company_id', 'company_name', 'company_logo']);

        if (!$dbCompany) return null;

        // ✅ Inject DB name and logo — never use hardcoded values
        $config['name'] = $dbCompany->company_name;
        $config['logo'] = $dbCompany->logo_url;

        return $config;
    }

    // =========================================================================
    // ✅ FALLBACK lookup — by company name string from DB
    // Used by confirmBooking flow where only company name is available
    // =========================================================================
    private function findCompanyConfig(string $companyName): ?array
    {
        // Load all active bus companies from DB keyed by company_id
        $dbCompanies = Company::where('company_type', 'bus')
            ->where('is_active', true)
            ->get(['company_id', 'company_name', 'company_logo'])
            ->keyBy('company_id');

        foreach ($this->companies as $config) {
            $dbMatch = $dbCompanies->get((int) $config['operator_id']);
            if (!$dbMatch) continue;

            // Match by DB company_name (case-insensitive)
            if (strtolower($dbMatch->company_name) === strtolower($companyName)) {
                $config['name'] = $dbMatch->company_name;
                $config['logo'] = $dbMatch->logo_url;
                return $config;
            }
        }

        return null;
    }

    // =========================================================================
    // GET /api/seats
    // ✅ Now resolves company config via operator_id → DB company_id match
    // =========================================================================
    public function index(Request $request)
    {
        try {
            $companyName   = $request->query('company', '');
            $from          = $request->query('from', '');      // global city ID
            $to            = $request->query('to', '');        // global city ID
            $date          = $request->query('date', '');
            $time          = $request->query('time', '');
            $serviceTypeId = $request->query('serviceTypeId', '');
            $scheduleId    = $request->query('scheduleId', '');
            $operatorId    = $request->query('operator_id', '');

            if (empty($companyName) || empty($from) || empty($to) ||
                empty($date) || empty($time) || empty($serviceTypeId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters.'
                ], 400);
            }

            if (empty($operatorId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'operator_id is required.'
                ], 400);
            }

            // Resolve company config
            $companyConfig = $this->findCompanyByOperatorId($operatorId);

            if (!$companyConfig) {
                return response()->json([
                    'success' => false,
                    'message' => "No active company found for operator_id \"{$operatorId}\"."
                ], 404);
            }

            if (empty($scheduleId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule ID is required.'
                ], 400);
            }

            // ✅ Convert global city IDs to company-specific city IDs
            $operatorIdInt = (int) $operatorId;
            $globalFromId  = (int) $from;
            $globalToId    = (int) $to;

            $companyFromId = $this->resolveCompanyCityId($operatorIdInt, $globalFromId);
            $companyToId   = $this->resolveCompanyCityId($operatorIdInt, $globalToId);

            if ($companyFromId === null) {
                return response()->json([
                    'success' => false,
                    'message' => "No city mapping found for source city ID {$globalFromId} with operator {$operatorId}"
                ], 400);
            }

            if ($companyToId === null) {
                return response()->json([
                    'success' => false,
                    'message' => "No city mapping found for destination city ID {$globalToId} with operator {$operatorId}"
                ], 400);
            }

            // Use mapped company-specific IDs for the external API
            $response = Http::timeout(10)
                ->withOptions(['connect_timeout' => 3])
                ->get($companyConfig['seat_api_url'], [
                    'sid'      => $scheduleId,
                    'date'     => $date,
                    'time'     => $time,
                    'username' => $companyConfig['username'],
                    'password' => $companyConfig['password'],
                    'from'     => $companyFromId,   // ✅ mapped ID
                    'to'       => $companyToId,     // ✅ mapped ID
                    'svid'     => $serviceTypeId,
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch seat data. Please try again later.'
                ], 500);
            }

            $data = $response->json();

            if (!is_array($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid seat data format.'
                ], 500);
            }

            // Pass mapped IDs to processSeatData for fare calculations
            $processedSeats = $this->processSeatData($data, $companyToId, $companyFromId, $serviceTypeId, $companyConfig);
            $availableSeats = $this->countAvailableSeats($processedSeats);

            // Discount: use global destination ID ($to) because discount table stores global city IDs
            $discount = null;
            try {
                $allActiveDiscounts = Discount::active()->get();
                foreach ($allActiveDiscounts as $d) {
                    // City match: main_city_id OR mapped_city_ids contains global $to
                    $cityMatches = ($d->main_city_id == $to);
                    if (!$cityMatches && is_array($d->mapped_city_ids)) {
                        $cityMatches = in_array($to, $d->mapped_city_ids) ||
                                    in_array((string)$to, $d->mapped_city_ids);
                    }
                    if (!$cityMatches) continue;

                    // Company match: null/empty means all companies
                    $companyMatches = is_null($d->company_ids) || empty($d->company_ids);
                    if (!$companyMatches && is_array($d->company_ids)) {
                        $companyMatches = in_array($operatorId, $d->company_ids) ||
                                        in_array((string)$operatorId, $d->company_ids) ||
                                        in_array((int)$operatorId, $d->company_ids);
                    }
                    if ($companyMatches) {
                        $discount = $d;
                        break;
                    }
                }
            } catch (Exception $e) {
                Log::warning('Discount fetch failed in index', ['error' => $e->getMessage()]);
            }

            $discountPercentage = $discount ? $discount->discount_percentage : 0;

            if ($discount) {
                $processedSeats = array_map(function ($seat) use ($discountPercentage) {
                    $seat['original_fare'] = $seat['fare'];
                    $seat['fare']          = round($seat['fare'] - ($seat['fare'] * $discountPercentage / 100), 2);
                    $seat['has_discount']  = true;
                    return $seat;
                }, $processedSeats);
            }

            $baseFare           = $processedSeats[0]['fare']          ?? 0;
            $seat20OriginalFare = $processedSeats[19]['original_fare'] ?? ($processedSeats[19]['fare'] ?? 0);
            $seat20Fare         = $processedSeats[19]['fare']          ?? 0;

            return response()->json([
                'success'             => true,
                'company'             => $companyConfig['name'],
                'operator_id'         => $operatorId,
                'scheduleId'          => $scheduleId,
                'from'                => $from,                 // original global from ID for reference
                'to'                  => $to,                   // original global to ID for reference
                'company_from_id'     => $companyFromId,        // mapped ID used for API
                'company_to_id'       => $companyToId,          // mapped ID used for API
                'date'                => $date,
                'time'                => $time,
                'serviceTypeId'       => $serviceTypeId,
                'has_discount'        => (bool) $discount,
                'original_fare'       => $seat20OriginalFare,
                'seat_4_fare'         => $processedSeats[3]['fare'] ?? $baseFare,
                'seat_20_fare'        => $seat20OriginalFare,
                'seat_20_fare_dis'    => $seat20Fare,
                'discounted_fare'     => $baseFare,
                'discount_percentage' => $discountPercentage,
                'total_fare'          => $baseFare,
                'baseFare'            => $baseFare,
                'seats'               => $processedSeats,
                'totalSeats'          => count($processedSeats),
                'availableSeats'      => $availableSeats,
                'discount'            => $discount ? [
                    'percentage' => $discountPercentage,
                    'name'       => $discount->name,
                    'active'     => true,
                ] : null,
                'message' => 'Fetched ' . count($processedSeats) . ' seats, ' . $availableSeats . ' available.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->sanitizeError($e, 'index')
            ], 500);
        }
    }

    // =========================================================================
    // POST /api/seats/hold
    // ✅ Resolves by operator_id if provided, falls back to company name.
    // ✅ Robust timeout, connection error, and non-JSON response handling.
    // =========================================================================
    public function holdSeats(Request $request)
    {
        try {
            $request->validate([
                'company'     => 'required|string',
                'scheduleId'  => 'required|string',
                'seatId'      => 'required',
                'operator_id' => 'nullable|string',
            ]);

            // ── Resolve company config ────────────────────────────────────────
            // Prefer operator_id lookup; fall back to name-based lookup
            $companyConfig = $request->filled('operator_id')
                ? $this->findCompanyByOperatorId($request->operator_id)
                : $this->findCompanyConfig($request->company);

            if (!$companyConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company configuration not found. Cannot hold seat.',
                ], 404);
            }

            // ── Validate the config has the required keys ─────────────────────
            if (empty($companyConfig['hold_api_url'])) {
                Log::error('holdSeats: hold_api_url missing from config', [
                    'company'     => $request->company,
                    'operator_id' => $request->operator_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Hold API is not configured for this company.',
                ], 500);
            }

            // ── Call the external hold API ────────────────────────────────────
            // timeout(10)  — wait up to 10 seconds for a response
            // connectTimeout(5) — abort if connection cannot be established in 5 s
            try {
                $response = Http::timeout(10)
                    ->connectTimeout(5)
                    ->retry(2, 500) // retry up to 2 times with 500 ms delay
                    ->get($companyConfig['hold_api_url'], [
                        'sid'      => $request->seatId,
                        'username' => $companyConfig['username'],
                        'password' => $companyConfig['password'],
                    ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Network-level failure — connection refused, DNS failure, timeout
                Log::error('holdSeats: Connection to external API failed', [
                    'url'     => $companyConfig['hold_api_url'],
                    'seatId'  => $request->seatId,
                    'error'   => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to reach the seat reservation system. Please try again.',
                ], 503); // 503 Service Unavailable — not our fault
            }

            // ── Parse the response body safely ───────────────────────────────
            // External APIs sometimes return HTML error pages or plain text
            // instead of JSON when they encounter internal errors.
            $rawBody = $response->body();
            $result  = null;

            if (!empty($rawBody)) {
                // Attempt JSON decode; if it fails we still have $rawBody for logging
                $decoded = json_decode($rawBody, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Some operators wrap the result in a single-element array
                    $result = (is_array($decoded) && isset($decoded[0]))
                        ? $decoded[0]
                        : $decoded;
                } else {
                    // Non-JSON body (HTML / plain text error page from external API)
                    Log::warning('holdSeats: External API returned non-JSON body', [
                        'seatId'      => $request->seatId,
                        'status_code' => $response->status(),
                        'body'        => substr($rawBody, 0, 300), // cap log size
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Seat reservation system returned an unexpected response. Please try again.',
                    ], 502); // 502 Bad Gateway — upstream returned garbage
                }
            }

            // ── Evaluate the external API's own status field ──────────────────
            // Guard against $result being null or non-array before accessing keys
            if (!is_array($result)) {
                Log::warning('holdSeats: Parsed result is not an array', [
                    'seatId' => $request->seatId,
                    'result' => $result,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unexpected response format from seat reservation system.',
                ], 502);
            }

            $status = strtoupper(trim($result['status'] ?? ''));

            // ── HTTP-level failure (4xx / 5xx from the external API) ──────────
            if ($response->failed()) {
                Log::warning('holdSeats: External API returned HTTP error', [
                    'seatId'      => $request->seatId,
                    'status_code' => $response->status(),
                    'result'      => $result,
                ]);
                return response()->json([
                    'success'    => false,
                    'message'    => $result['message'] ?? 'Seat hold failed (external API error).',
                    'api_result' => $result,
                ], 400);
            }

            // ── Business-logic success check ──────────────────────────────────
            // Accept both "SUCCESS" and the typo variant "SUCESS" seen in the wild
            if (in_array($status, ['SUCCESS', 'SUCESS'])) {
                return response()->json([
                    'success'    => true,
                    'api_result' => $result,
                ]);
            }

            // ── External API returned 200 but status !== SUCCESS ──────────────
            Log::info('holdSeats: External API responded but seat hold was not successful', [
                'seatId' => $request->seatId,
                'status' => $status,
                'result' => $result,
            ]);

            return response()->json([
                'success'    => false,
                'message'    => $result['message'] ?? 'Seat hold was not confirmed by the reservation system.',
                'api_result' => $result,
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors as 422 — not 500
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Catch-all for unexpected errors — log full context
            Log::error('holdSeats: Unexpected error', [
                'seatId'  => $request->seatId ?? null,
                'company' => $request->company ?? null,
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $this->sanitizeError($e, 'holdSeats'),
            ], 500);
        }
    }

    // =========================================================================
    // POST /api/seats/unhold
    // ✅ Resolves by operator_id if provided, falls back to company name.
    // ✅ Robust timeout, connection error, and non-JSON response handling.
    // =========================================================================
    public function unholdSeats(Request $request)
    {
        try {
            $request->validate([
                'company'     => 'required|string',
                'scheduleId'  => 'required|string',
                'seatId'      => 'required',
                'operator_id' => 'nullable|string',
            ]);

            // ── Resolve company config ────────────────────────────────────────
            $companyConfig = $request->filled('operator_id')
                ? $this->findCompanyByOperatorId($request->operator_id)
                : $this->findCompanyConfig($request->company);

            if (!$companyConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company configuration not found. Cannot unhold seat.',
                ], 404);
            }

            // ── Validate the config has the required keys ─────────────────────
            if (empty($companyConfig['unhold_api_url'])) {
                Log::error('unholdSeats: unhold_api_url missing from config', [
                    'company'     => $request->company,
                    'operator_id' => $request->operator_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unhold API is not configured for this company.',
                ], 500);
            }

            // ── Call the external unhold API ──────────────────────────────────
            // Unhold is even more critical than hold because a stuck held seat
            // blocks other users — use a slightly longer timeout and more retries.
            // timeout(12)       — wait up to 12 seconds
            // connectTimeout(5) — abort connection attempt after 5 s
            // retry(3, 300)     — retry up to 3 times with 300 ms delay
            try {
                $response = Http::timeout(12)
                    ->connectTimeout(5)
                    ->retry(3, 300)
                    ->get($companyConfig['unhold_api_url'], [
                        'sid'      => $request->seatId,
                        'uid'      => $companyConfig['User_Id'],
                        'username' => $companyConfig['username'],
                        'password' => $companyConfig['password'],
                    ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Network-level failure — log but treat as a soft failure.
                // The frontend will still remove the seat from its local state
                // even when this returns non-200, so we keep the error surface clean.
                Log::error('unholdSeats: Connection to external API failed', [
                    'url'    => $companyConfig['unhold_api_url'],
                    'seatId' => $request->seatId,
                    'error'  => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to reach the seat reservation system to release the seat.',
                ], 503);
            }

            // ── Parse the response body safely ───────────────────────────────
            $rawBody = $response->body();
            $result  = null;

            if (!empty($rawBody)) {
                $decoded = json_decode($rawBody, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $result = (is_array($decoded) && isset($decoded[0]))
                        ? $decoded[0]
                        : $decoded;
                } else {
                    // Non-JSON response from external API
                    Log::warning('unholdSeats: External API returned non-JSON body', [
                        'seatId'      => $request->seatId,
                        'status_code' => $response->status(),
                        'body'        => substr($rawBody, 0, 300),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Seat release system returned an unexpected response.',
                    ], 502);
                }
            }

            // ── Guard against non-array result ────────────────────────────────
            if (!is_array($result)) {
                Log::warning('unholdSeats: Parsed result is not an array', [
                    'seatId' => $request->seatId,
                    'result' => $result,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unexpected response format from seat release system.',
                ], 502);
            }

            $status = strtoupper(trim($result['status'] ?? ''));

            // ── HTTP-level failure (4xx / 5xx from the external API) ──────────
            if ($response->failed()) {
                Log::warning('unholdSeats: External API returned HTTP error', [
                    'seatId'      => $request->seatId,
                    'status_code' => $response->status(),
                    'result'      => $result,
                ]);
                return response()->json([
                    'success'    => false,
                    'message'    => $result['message'] ?? 'Seat release failed (external API error).',
                    'api_result' => $result,
                ], 400);
            }

            // ── Business-logic success check ──────────────────────────────────
            if (in_array($status, ['SUCCESS', 'SUCESS'])) {
                return response()->json([
                    'success'    => true,
                    'message'    => 'Seat released successfully.',
                    'api_result' => $result,
                ]);
            }

            // ── External API returned 200 but status !== SUCCESS ──────────────
            Log::info('unholdSeats: External API responded but seat unhold was not successful', [
                'seatId' => $request->seatId,
                'status' => $status,
                'result' => $result,
            ]);

            return response()->json([
                'success'    => false,
                'message'    => $result['message'] ?? 'Seat release was not confirmed by the reservation system.',
                'api_result' => $result,
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('unholdSeats: Unexpected error', [
                'seatId'  => $request->seatId ?? null,
                'company' => $request->company ?? null,
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $this->sanitizeError($e, 'unholdSeats'),
            ], 500);
        }
    }

    // =========================================================================
    // GET /api/terminals
    // ✅ Now resolves by operator_id if provided, falls back to company name
    // =========================================================================
    public function getSourceTerminals(Request $request)
    {
        try {
            $request->validate([
                'company'       => 'required|string',
                'scheduleId'    => 'required|string',
                'fromId'        => 'required|string',
                'departureTime' => 'required|string',
                'serviceTypeId' => 'required|string',
                'operator_id'   => 'nullable|string',
            ]);

            // ✅ Prefer operator_id lookup, fallback to name
            $companyConfig = $request->filled('operator_id')
                ? $this->findCompanyByOperatorId($request->operator_id)
                : $this->findCompanyConfig($request->input('company'));

            if (!$companyConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], 404);
            }

            if (!isset($companyConfig['terminal_api_url'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terminal API not configured for this company'
                ], 400);
            }

            // ✅ Resolve global fromId → company-specific city ID
            $operatorId = (int) ($request->input('operator_id') ?? $companyConfig['operator_id']);
            $globalFromId = (int) $request->input('fromId');
            $companyFromId = $this->resolveCompanyCityId($operatorId, $globalFromId);

            if ($companyFromId === null) {
                return response()->json([
                    'success' => false,
                    'message' => "No city mapping found for global city ID {$globalFromId} with operator {$operatorId}"
                ], 400);
            }

            $response = Http::timeout(30)->get($companyConfig['terminal_api_url'], [
                'sid'      => $companyFromId,      // ✅ use company-specific ID
                'schid'    => $request->input('scheduleId'),
                'dtime'    => $request->input('departureTime'),
                'svid'     => $request->input('serviceTypeId'),
                'username' => $companyConfig['username'],
                'password' => $companyConfig['password'],
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch terminals. Please try again later.',
                ], 500);
            }

            $terminals = $this->processTerminalData($response->json());

            return response()->json([
                'success'        => true,
                'company'        => $companyConfig['name'],
                'operator_id'    => $operatorId,
                'scheduleId'     => $request->input('scheduleId'),
                'fromId'         => $globalFromId,          // keep original global ID for reference
                'companyFromId'  => $companyFromId,         // show resolved ID
                'departureTime'  => $request->input('departureTime'),
                'serviceTypeId'  => $request->input('serviceTypeId'),
                'terminals'      => $terminals,
                'totalTerminals' => count($terminals),
                'message'        => 'Fetched ' . count($terminals) . ' terminals',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->sanitizeError($e, 'getSourceTerminals')
            ], 500);
        }
    }

    private function resolveCompanyCityId(int $operatorId, int $globalCityId): ?int
    {
        try {
            $mapping = CompanyCity::where('company_id', $operatorId)
                ->where('key_id', $globalCityId)
                ->where('active', true)
                ->first();

            if ($mapping) {
                return (int) $mapping->city_id;
            }

            Log::info('No CompanyCity mapping found', [
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
            ]);
            return null;

        } catch (Exception $e) {
            Log::warning('resolveCompanyCityId failed', [
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
                'error'          => $e->getMessage(),
            ]);
            return null;
        }
    }

    // =========================================================================
    // POST /api/seats/fare — UNCHANGED except discount uses operator_id
    // =========================================================================
    public function verifyFare(Request $request)
    {
        try {
            $companyName   = $request->input('company', '');
            $sourceId      = $request->input('sourceId', '');
            $destinationId = $request->input('destinationId', '');
            $serviceTypeId = $request->input('serviceTypeId', '');
            $seatNo        = $request->input('seatNo', '');
            $operatorId    = $request->input('operator_id', '');

            if (empty($companyName) || empty($sourceId) || empty($destinationId) ||
                empty($serviceTypeId) || empty($seatNo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters.'
                ], 400);
            }

            // ✅ Prefer operator_id lookup, fallback to name
            $companyConfig = !empty($operatorId)
                ? $this->findCompanyByOperatorId($operatorId)
                : $this->findCompanyConfig($companyName);

            if (!$companyConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], 404);
            }

            $response = Http::timeout(10)->get($companyConfig['fare_api_url'], [
                'Source_id'      => $sourceId,
                'Destination_id' => $destinationId,
                'ServiceType_Id' => $serviceTypeId,
                'Seat_No'        => $seatNo,
                'username'       => $companyConfig['username'],
                'password'       => $companyConfig['password'],
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify fare. Please try again later.'
                ], 500);
            }

            $data     = $response->json();
            $fareData = is_array($data) && isset($data[0]) ? $data[0] : $data;
            $baseFare = (float) ($fareData['fare'] ?? 0);

            // ✅ DISCOUNT: Use PHP filtering with destination city ID and operator_id
            $discount = null;
            try {
                $allActiveDiscounts = Discount::active()->get();
                foreach ($allActiveDiscounts as $d) {
                    // City match: main_city_id OR mapped_city_ids contains $destinationId
                    $cityMatches = ($d->main_city_id == $destinationId);
                    if (!$cityMatches && is_array($d->mapped_city_ids)) {
                        $cityMatches = in_array($destinationId, $d->mapped_city_ids) ||
                                    in_array((string)$destinationId, $d->mapped_city_ids);
                    }
                    if (!$cityMatches) continue;

                    // Company match: null/empty means all companies
                    $companyMatches = is_null($d->company_ids) || empty($d->company_ids);
                    if (!$companyMatches && is_array($d->company_ids)) {
                        $companyMatches = in_array($operatorId, $d->company_ids) ||
                                        in_array((string)$operatorId, $d->company_ids) ||
                                        in_array((int)$operatorId, $d->company_ids);
                    }
                    if ($companyMatches) {
                        $discount = $d;
                        break;
                    }
                }
            } catch (Exception $e) {
                Log::warning('Discount fetch failed in verifyFare', ['error' => $e->getMessage()]);
            }

            $fareResult = [
                'fare'                => $baseFare,
                'base_fare'           => $baseFare,
                'discount_percentage' => 0,
                'discounted_fare'     => $baseFare,
                'has_discount'        => false,
            ];

            if ($discount) {
                $discountedFare = $baseFare - ($baseFare * $discount->discount_percentage / 100);
                $fareResult     = [
                    'fare'                => $baseFare,
                    'base_fare'           => $baseFare,
                    'discount_percentage' => $discount->discount_percentage,
                    'discounted_fare'     => round($discountedFare, 2),
                    'discount_amount'     => round($baseFare - $discountedFare, 2),
                    'has_discount'        => true,
                    'discount_name'       => $discount->name,
                ];
            }

            return response()->json([
                'success'   => true,
                'fare_data' => array_merge((array) $fareData, $fareResult),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->sanitizeError($e, 'verifyFare')
            ], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS — processSeatData, countAvailableSeats,
    // processTerminalData all UNCHANGED
    // =========================================================================
    private function processSeatData($seats, $toId = null, $fromId = null, $serviceTypeId = null, $companyConfig = null)
    {
        if (!is_array($seats)) return [];

        $fareCache   = [];
        $defaultFare = 0;

        $seatsToFetch = array_slice($seats, 0, 3);

        if ($companyConfig && isset($companyConfig['fare_api_url']) && $fromId && $toId && $serviceTypeId) {
            try {
                $responses = Http::pool(function ($pool) use ($seatsToFetch, $companyConfig, $fromId, $toId, $serviceTypeId) {
                    $requests = [];
                    foreach ($seatsToFetch as $seat) {
                        $seatNo = $seat['Seat_No'] ?? null;
                        if ($seatNo) {
                            $requests["seat_{$seatNo}"] = $pool->as("seat_{$seatNo}")
                                ->timeout(3)
                                ->withOptions(['connect_timeout' => 2])
                                ->get($companyConfig['fare_api_url'], [
                                    'Source_id'      => $fromId,
                                    'Destination_id' => $toId,
                                    'ServiceType_Id' => $serviceTypeId,
                                    'Seat_No'        => $seatNo,
                                    'username'       => $companyConfig['username'],
                                    'password'       => $companyConfig['password'],
                                ]);
                        }
                    }
                    return $requests;
                });

                foreach ($seatsToFetch as $seat) {
                    $seatNo = $seat['Seat_No'] ?? null;
                    if (!$seatNo) continue;

                    $key = "seat_{$seatNo}";
                    if (isset($responses[$key]) && !($responses[$key] instanceof \Throwable)) {
                        $fareResponse = $responses[$key];
                        if ($fareResponse->successful()) {
                            $fareData    = $fareResponse->json();
                            $fetchedFare = (float) ($fareData[0]['fare'] ?? 0);
                            if ($fetchedFare > 0) {
                                $fareCache[$seatNo] = $fetchedFare;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log silently — continue with default fares
                Log::warning('processSeatData fare fetch failed', ['error' => $e->getMessage()]);
            }
        }

        if (!empty($fareCache)) {
            $defaultFare = reset($fareCache);
        }

        $processedSeats = [];

        foreach ($seats as $seat) {
            $seatNo = $seat['Seat_No'] ?? null;

            if (isset($fareCache[$seatNo])) {
                $apiFare = $fareCache[$seatNo];
            } elseif (isset($seat['fare'])) {
                $apiFare = floatval($seat['fare']);
            } elseif (isset($seat['Fare'])) {
                $apiFare = floatval($seat['Fare']);
            } else {
                $apiFare = $defaultFare;
            }

            $normalizedSeat = [
                'Seat_No'         => $seatNo,
                'SeatNo'          => $seatNo,
                'fare'            => $apiFare,
                'Gender'          => $seat['Gender']          ?? '',
                'Status'          => $seat['Status']          ?? 0,
                'seat_id'         => $seat['seat_id']         ?? null,
                'seat_name'       => $seat['seat_name']       ?? '',
                'seat_type'       => $seat['seat_type']       ?? 'Seat',
                'seat_status'     => $seat['seat_status']     ?? 'Empty',
                'row_name'        => $seat['row_name']        ?? '',
                'row_index'       => $seat['row_index']       ?? 0,
                'col_index'       => $seat['col_index']       ?? 0,
                'AreaCategoryCod' => $seat['AreaCategoryCod'] ?? 'Regular',
                'Route_Sr_No'     => $seat['Route_Sr_No']     ?? '0',
                'has_discount'    => false,
            ];

            $seatStatus = strtolower(trim($normalizedSeat['seat_status']));

            switch ($seatStatus) {
                case 'available':
                    $normalizedSeat['Status'] = 1;
                    break;
                case 'reserved':
                    $normalizedSeat['Status']      = 3;
                    $normalizedSeat['seat_status'] = 'Reserved';
                    break;
                case 'hold':
                    $normalizedSeat['Status'] = 2;
                    break;
                case 'empty':
                    $normalizedSeat['Status'] = 0;
                    break;
                default:
                    $normalizedSeat['Status'] = 0;
                    break;
            }

            $processedSeats[] = $normalizedSeat;
        }

        return $processedSeats;
    }

    private function countAvailableSeats($seats)
    {
        if (!is_array($seats)) return 0;

        $count = 0;
        foreach ($seats as $seat) {
            if (isset($seat['seat_status']) && strtolower($seat['seat_status']) === 'available') {
                $count++;
            }
        }
        return $count;
    }

    private function processTerminalData($apiTerminals)
    {
        if (!is_array($apiTerminals)) return [];

        $processed = [];
        foreach ($apiTerminals as $terminal) {
            $processed[] = [
                'terminalId'    => $terminal['Id']           ?? null,
                'terminalName'  => $terminal['TerminalName'] ?? 'Unknown Terminal',
                'departureTime' => $terminal['DepTime']      ?? 'Unknown',
                'address'       => $terminal['Address']      ?? 'Unknown',
                'instructions'  => 'Check-in opens 30 minutes before departure',
            ];
        }
        return $processed;
    }
}
