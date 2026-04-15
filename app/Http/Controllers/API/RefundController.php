<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class RefundController extends Controller
{
    // =========================================================================
    // operator_id must match companies.company_id in DB
    // =========================================================================
    private $companies = [];

    public function __construct()
    {
        $this->companies = array_values(config('companies.bus', []));
    }

    // =========================================================================
    // Show refund tickets page
    // =========================================================================
    public function index(Request $request)
    {
        $query = TicketingSeat::with(['fromCity', 'toCity'])
            ->whereIn('Status', ['Cancelled', 'Pending Refund'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No', 'like', "%{$search}%")
                    ->orWhere('Passenger_Name', 'like', "%{$search}%")
                    ->orWhere('Contact_No', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company'))   $query->where('Company_Name', $request->company);
        if ($request->filled('status'))    $query->where('Status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        $tickets = $query->paginate(20)->withQueryString();

        $stats = [
            'total_cancelled' => TicketingSeat::where('Status', 'Cancelled')->count(),
            'pending_refund'  => TicketingSeat::where('Status', 'Pending Refund')->count(),
            'total_pending'   => TicketingSeat::where('Status', 'Pending Refund')->count(),
        ];

        $ticketCompanies = TicketingSeat::whereIn('Status', ['Cancelled', 'Pending Refund'])
            ->distinct()
            ->whereNotNull('Company_Name')
            ->pluck('Company_Name')
            ->filter()
            ->values();

        // ✅ Return operator_id so frontend can send it back in the refund payload
        $refundCompanies = collect($this->companies)->map(fn($c) => [
            'operator_id' => $c['operator_id'],
            'name'        => $c['name'],
            'logo'        => $c['logo'] ?? null,
        ])->values()->toArray();

        return Inertia::render('Admin/Refund/Index', [
            'tickets'         => $tickets,
            'stats'           => $stats,
            'ticketCompanies' => $ticketCompanies,
            'refundCompanies' => $refundCompanies,
            'filters'         => [
                'search'    => $request->search    ?? '',
                'company'   => $request->company   ?? '',
                'status'    => $request->status    ?? '',
                'date_from' => $request->date_from ?? '',
                'date_to'   => $request->date_to   ?? '',
            ],
        ]);
    }

    // =========================================================================
    // POST /admin/refund/process
    // =========================================================================
    public function processRefund(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'pnr_no'            => 'required|string',
                'company_id'        => 'required|string',   // ✅ use company_id (operator_id)
                'refund_percentage' => 'required|numeric|min:0|max:100',
                'refund_amount'     => 'required|numeric|min:0',
            ]);

            // ✅ Find ticket by PNR
            $ticket = TicketingSeat::where('PNR_No', $validated['pnr_no'])->first();

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'PNR not found'], 404);
            }

            if ($ticket->Status !== 'Pending Refund') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket is not in Pending Refund state',
                ], 400);
            }

            // Validate refund amount
            $maxRefund = $ticket->Fare * ($validated['refund_percentage'] / 100);
            if ($validated['refund_amount'] > $maxRefund) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund amount exceeds allowed limit',
                ], 400);
            }

            // ✅ Find company by operator_id (company_id from payload)
            $companyConfig = $this->findCompanyById($validated['company_id']);

            if (!$companyConfig) {
                return response()->json([
                    'success' => false,
                    'message' => "Company config not found for ID: {$validated['company_id']}",
                ], 404);
            }

            // Call Refund API
            $apiResponse = $this->callRefundAPI($companyConfig, $ticket);

            if (!$this->isRefundAPISuccessful($apiResponse)) {
                DB::rollBack();
                return response()->json([
                    'success'      => false,
                    'message'      => 'Refund API failed',
                    'api_response' => $apiResponse,
                ], 400);
            }

            // Update ticket to Cancelled
            $ticket->update([
                'Status'             => 'Cancelled',
                'collection_point' => 'Refunded',
                'Refund_Amount'      => $validated['refund_amount'],
                'Refund_Percentage'  => $validated['refund_percentage'],
                'Refund_Date'        => now(),
                'Refund_By'          => auth()->id(),
                'Is_Return'          => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data'    => [
                    'pnr'               => $ticket->PNR_No,
                    'seat_no'           => $ticket->Seat_No,
                    'passenger'         => $ticket->Passenger_Name,
                    'refund_amount'     => $ticket->Refund_Amount,
                    'refund_percentage' => $ticket->Refund_Percentage,
                    'company'           => $companyConfig['name'],
                    'api_response'      => $apiResponse,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund error', ['message' => $e->getMessage(), 'pnr' => $request->pnr_no ?? null]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    // ✅ Find by operator_id — matches the company_id sent from frontend
    private function findCompanyById(string $companyId): ?array
    {
        return collect($this->companies)
            ->firstWhere('operator_id', $companyId);
    }

    // Keep name lookup as fallback (used nowhere currently but handy)
    private function findCompanyByName(string $companyName): ?array
    {
        return collect($this->companies)
            ->first(fn($c) => strcasecmp($c['name'], $companyName) === 0);
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
            'company'    => $companyConfig['name'],
            'pnr'        => $ticket->PNR_No,
            'status'     => $response->status(),
        ]);

        return $response->json();
    }

    private function isRefundAPISuccessful(mixed $apiResponse): bool
    {
        if (!is_array($apiResponse)) return false;

        // ✅ Handle both 'SUCESS' (typo in API) and 'SUCCESS'
        $status = strtoupper($apiResponse[0]['status'] ?? '');
        return in_array($status, ['SUCESS', 'SUCCESS']);
    }
}
