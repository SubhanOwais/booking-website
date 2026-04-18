<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use App\Models\City;
use App\Models\CompanyCity;   // <-- add this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RefundController extends Controller
{
    private array $companies = [];

    public function __construct()
    {
        $this->companies = array_values(config('companies.bus', []));
    }

    // =========================================================================
    // GET /refund  — render page shell only (no ticket data)
    // =========================================================================
    public function index()
    {
        $companyId = Auth::user()->Company_Id;

        $stats = $this->buildStats($companyId);

        $companyConfig = $this->findCompanyByDbId($companyId);

        return Inertia::render('Company/Refund/Index', [
            'stats'         => $stats,
            'companyConfig' => $companyConfig ? [
                'operator_id' => $companyConfig['operator_id'] ?? null,
                'name'        => $companyConfig['name']        ?? 'Your Company',
            ] : ['operator_id' => null, 'name' => 'Your Company'],
        ]);
    }

    // =========================================================================
    // GET /refund/data  — JSON: paginated tickets (called by Vue on mount + filters)
    // =========================================================================
    public function getRefunds(Request $request)
    {
        $companyId = Auth::user()->Company_Id;

        $tickets = $this->buildTicketQuery($companyId, $request)
            ->paginate(20)
            ->withQueryString();

        // Flatten city names so Vue doesn't have to dig into nested relations
        $tickets->getCollection()->transform(fn($t) => $this->mapTicket($t));

        $stats = $this->buildStats($companyId);

        return response()->json([
            'tickets' => $tickets,
            'stats'   => $stats,
        ]);
    }

    // =========================================================================
    // GET /refund/live  — JSON: same as getRefunds (used by background polling)
    // =========================================================================
    public function live(Request $request)
    {
        return $this->getRefunds($request);
    }

    // =========================================================================
    // POST /refund/process
    // =========================================================================
    public function processRefund(Request $request)
    {
        $companyId = Auth::user()->Company_Id;

        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'pnr_no'            => 'required|string',
                'refund_percentage' => 'required|numeric|min:0|max:100',
                'refund_amount'     => 'required|numeric|min:0',
            ]);

            // Lock row — prevents two agents processing the same ticket simultaneously
            $ticket = TicketingSeat::where('PNR_No', $validated['pnr_no'])
                ->where('Company_Id', $companyId)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'PNR not found for your company.',
                ], 404);
            }

            if ($ticket->Status !== 'Pending Refund') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This ticket has already been processed by another user.',
                ], 409);
            }

            // Validate amount ceiling
            $maxRefund = $ticket->Fare * ($validated['refund_percentage'] / 100);
            if ($validated['refund_amount'] > $maxRefund + 0.01) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Refund amount exceeds the allowed limit.',
                ], 400);
            }

            // ── External API call (optional — skipped if config not found) ───
            $companyConfig = $this->findCompanyByDbId($companyId);

            if ($companyConfig) {
                $apiResponse = $this->callRefundAPI($companyConfig, $ticket);

                if (!$this->isRefundAPISuccessful($apiResponse)) {
                    DB::rollBack();
                    return response()->json([
                        'success'      => false,
                        'message'      => 'External refund API call failed. Please try again.',
                        'api_response' => $apiResponse,
                    ], 400);
                }
            } else {
                // No external API config — log and continue with local processing only
                Log::warning('Refund processed without external API (no config found)', [
                    'company_id' => $companyId,
                    'pnr'        => $ticket->PNR_No,
                ]);
            }

            // ── Update ticket ─────────────────────────────────────────────────
            $ticket->update([
                'Status'            => 'Cancelled',
                'collection_point'  => 'Refunded',
                'Refund_Amount'     => $validated['refund_amount'],
                'Refund_Percentage' => $validated['refund_percentage'],
                'Refund_Date'       => now(),
                'Refund_By'         => auth()->id(),
                'Is_Return'         => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully.',
                'data'    => [
                    'pnr'               => $ticket->PNR_No,
                    'passenger'         => $ticket->Passenger_Name,
                    'refund_amount'     => $ticket->Refund_Amount,
                    'refund_percentage' => $ticket->Refund_Percentage,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company refund error', [
                'company_id' => $companyId,
                'pnr'        => $request->pnr_no ?? null,
                'message'    => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Build the base TicketingSeat query scoped to the company with all filters.
     */
    private function buildTicketQuery(int|string $companyId, Request $request)
    {
        $query = TicketingSeat::query()
            ->where('Company_Id', $companyId)
            ->whereIn('Status', ['Cancelled', 'Pending Refund'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('PNR_No',         'like', "%{$s}%")
                  ->orWhere('Passenger_Name','like', "%{$s}%")
                  ->orWhere('Contact_No',    'like', "%{$s}%");
            });
        }

        if ($request->filled('status'))    $query->where('Status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        return $query;
    }

    /**
     * Map a TicketingSeat model to a plain array with flat city name strings.
     * City names are resolved directly from the City model using the Source/Destination IDs
     * stored on the ticket — no Eloquent relationship required on the model.
     */
    private function mapTicket(TicketingSeat $ticket): array
    {
        $companyId = Auth::user()->Company_Id;

        // Get company‑specific city names using the mapping table
        $fromCity = $this->getCompanyCityName($companyId, (int) $ticket->Source_ID);
        $toCity   = $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID);

        return [
            'id'               => $ticket->id,
            'PNR_No'           => $ticket->PNR_No,
            'Passenger_Name'   => $ticket->Passenger_Name,
            'Contact_No'       => $ticket->Contact_No,
            'Travel_Date'      => $ticket->Travel_Date,
            'Travel_Time'      => $ticket->Travel_Time,
            'Seat_No'          => $ticket->Seat_No,
            'Fare'             => $ticket->Fare,
            'Status'           => $ticket->Status,
            'Refund_Reason'    => $ticket->Refund_Reason   ?? null,
            'Refund_Amount'    => $ticket->Refund_Amount   ?? null,
            'Refund_Percentage'=> $ticket->Refund_Percentage ?? null,
            'Company_Id'       => $ticket->Company_Id,
            'Company_Name'     => $ticket->Company_Name    ?? null,
            'from_city_name'   => $fromCity,
            'to_city_name'     => $toCity,
        ];
    }

    /**
     * Resolve a city name from either an already-loaded relation or a raw city ID.
     */
    private function getCompanyCityName(int $operatorId, int $globalCityId): string
    {
        try {
            $mapping = CompanyCity::where('company_id', $operatorId)
                ->where('city_id', $globalCityId)
                ->where('active', true)
                ->first();

            if ($mapping && $mapping->key_id) {
                $cityName = City::where('id', $mapping->key_id)->value('City_Name');
                if ($cityName) return $cityName;
            }
        } catch (\Exception $e) {
            Log::warning('getCompanyCityName failed in RefundController', [
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
                'error'          => $e->getMessage(),
            ]);
        }

        // Fallback – direct city lookup by the global ID
        return City::where('id', $globalCityId)->value('City_Name') ?? 'Unknown';
    }

    /**
     * Build stats counts for the company.
     */
    private function buildStats(int|string $companyId): array
    {
        return [
            'total_cancelled' => TicketingSeat::where('Company_Id', $companyId)
                                    ->where('Status', 'Cancelled')->count(),
            'pending_refund'  => TicketingSeat::where('Company_Id', $companyId)
                                    ->where('Status', 'Pending Refund')->count(),
        ];
    }

    /**
     * Find company config by DB Company_Id.
     * Checks multiple possible config keys so it works regardless of your config structure.
     */
    private function findCompanyByDbId(int|string $dbId): ?array
    {
        return collect($this->companies)->first(function ($c) use ($dbId) {
            foreach (['company_db_id', 'Company_Id', 'db_id', 'id'] as $key) {
                if (isset($c[$key]) && (string)$c[$key] === (string)$dbId) {
                    return true;
                }
            }
            return false;
        });
    }

    private function callRefundAPI(array $companyConfig, TicketingSeat $ticket): mixed
    {
        $url = $companyConfig['refund_api'] . '?' . http_build_query([
            'invoice_Id' => $ticket->PNR_No,
            'username'   => $companyConfig['username'],
            'password'   => $companyConfig['password'],
        ]);

        $response = Http::timeout(20)->get($url);

        Log::info('Refund API called', [
            'company' => $companyConfig['name'] ?? 'unknown',
            'pnr'     => $ticket->PNR_No,
            'status'  => $response->status(),
        ]);

        return $response->json();
    }

    private function isRefundAPISuccessful(mixed $apiResponse): bool
    {
        if (!is_array($apiResponse)) return false;
        $status = strtoupper($apiResponse[0]['status'] ?? '');
        return in_array($status, ['SUCESS', 'SUCCESS']);
    }
}
