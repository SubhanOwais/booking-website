<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyTicketingController extends Controller
{
    /**
     * Display a listing of tickets for the logged-in company.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->Company_Id;

        // Only allow company users (CompanyOwner / CompanyUser)
        if (!in_array($user->User_Type, ['CompanyOwner', 'CompanyUser'])) {
            abort(403, 'Unauthorized access.');
        }

        $query = TicketingSeat::with(['fromCity', 'toCity', 'user'])
            ->where('Company_Id', $companyId)
            ->orderBy('created_at', 'desc');

        // Apply filters...
        if ($request->filled('status')) {
            $query->where('Status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('Travel_Date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('Travel_Date', '<=', $request->date_to);
        }
        if ($request->filled('booked_from')) {
            $query->whereDate('created_at', '>=', $request->booked_from);
        }
        if ($request->filled('booked_to')) {
            $query->whereDate('created_at', '<=', $request->booked_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No', 'like', "%{$search}%")
                  ->orWhere('Passenger_Name', 'like', "%{$search}%")
                  ->orWhere('Contact_No', 'like', "%{$search}%");
            });
        }
        if ($request->filled('company')) {
            $query->where('Company_Name', $request->company);
        }

        $tickets = $query->paginate(50)->withQueryString();

        // ✅ Replace city names with company‑specific names
        $tickets->getCollection()->transform(function ($ticket) use ($companyId) {
            // Use setAttribute instead of setRelation — plain attributes always serialize to Inertia
            $ticket->setAttribute('fromCity', ['City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Source_ID)]);
            $ticket->setAttribute('toCity',   ['City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID)]);
            return $ticket;
        });

        // Statistics (only for this company)
        $stats = [
            'total'     => TicketingSeat::where('Company_Id', $companyId)->count(),
            'pending'   => TicketingSeat::where('Company_Id', $companyId)->where('Status', 'Pending')->count(),
            'confirmed' => TicketingSeat::where('Company_Id', $companyId)->where('Status', 'Confirmed')->count(),
            'cancelled' => TicketingSeat::where('Company_Id', $companyId)->where('Status', 'Cancelled')->count(),
        ];

        $companies = TicketingSeat::where('Company_Id', $companyId)
            ->distinct()
            ->whereNotNull('Company_Name')
            ->pluck('Company_Name')
            ->filter()
            ->values();

        return Inertia::render('Company/Ticketing/Index', [
            'tickets'   => $tickets,
            'stats'     => $stats,
            'companies' => $companies,
            'filters'   => [
                'status'      => $request->status ?? '',
                'date_from'   => $request->date_from ?? '',
                'date_to'     => $request->date_to ?? '',
                'booked_from' => $request->booked_from ?? '',
                'booked_to'   => $request->booked_to ?? '',
                'search'      => $request->search ?? '',
                'company'     => $request->company ?? '',
            ],
        ]);
    }

    /**
     * Display the specified ticket details.
     */
    public function show($id)
    {
        $user      = Auth::user();
        $companyId = $user->Company_Id;

        $ticket = TicketingSeat::with(['user'])   // ← removed fromCity/toCity (they return null anyway)
            ->where('Company_Id', $companyId)
            ->findOrFail($id);

        // Convert to plain array FIRST — then inject city names
        // This avoids Eloquent null-relation overwriting setAttribute values
        $ticketData = $ticket->toArray();

        $ticketData['from_city'] = [
            'City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Source_ID)
        ];
        $ticketData['to_city'] = [
            'City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID)
        ];

        if (request()->expectsJson()) {
            return response()->json(['ticket' => $ticketData]);
        }

        return Inertia::render('Company/Ticketing/Show', [
            'ticket' => $ticketData,
        ]);
    }

    /**
     * Export tickets to CSV (filtered by company and applied filters).
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->Company_Id;

        $query = TicketingSeat::with(['fromCity', 'toCity'])
            ->where('Company_Id', $companyId);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('Status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('Travel_Date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('Travel_Date', '<=', $request->date_to);
        }
        if ($request->filled('booked_from')) {
            $query->whereDate('created_at', '>=', $request->booked_from);
        }
        if ($request->filled('booked_to')) {
            $query->whereDate('created_at', '<=', $request->booked_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No', 'like', "%{$search}%")
                  ->orWhere('Passenger_Name', 'like', "%{$search}%")
                  ->orWhere('Contact_No', 'like', "%{$search}%");
            });
        }
        if ($request->filled('company')) {
            $query->where('Company_Name', $request->company);
        }

        $tickets = $query->get();

        $filename = 'company_tickets_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tickets, $companyId) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'PNR No',
                'Passenger Name',
                'Contact',
                'From',
                'To',
                'Travel Date',
                'Travel Time',
                'Seat No',
                'Fare',
                'Status',
                'Company',
                'Issue Date',
            ]);

            // Data rows
            foreach ($tickets as $ticket) {
                // ✅ Use company‑specific city names for export
                $sourceCityName = $this->getCompanyCityName($companyId, $ticket->Source_ID);
                $destCityName   = $this->getCompanyCityName($companyId, $ticket->Destination_ID);

                fputcsv($file, [
                    $ticket->PNR_No,
                    $ticket->Passenger_Name,
                    $ticket->Contact_No,
                    $sourceCityName,
                    $destCityName,
                    $ticket->Travel_Date,
                    $ticket->Travel_Time,
                    is_array($ticket->seatNumbers) ? implode(', ', $ticket->seatNumbers) : $ticket->Seat_No,
                    $ticket->Fare,
                    $ticket->Status,
                    $ticket->Company_Name,
                    $ticket->Issue_Date?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get company-specific city name using CompanyCity mapping
     *
     * @param int $operatorId  Company's ID (matches Company_Id in tickets)
     * @param int $globalCityId Global city ID (cities.id)
     * @return string
     */
    private function getCompanyCityName(int $operatorId, int $globalCityId): string
    {
        try {
            $mapping = \App\Models\CompanyCity::where('company_id', $operatorId)
                ->where('city_id', $globalCityId)
                ->where('active', true)
                ->first();

            Log::info('getCompanyCityName', [           // ← add this temporarily
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
                'mapping_found'  => (bool) $mapping,
                'key_id'         => $mapping?->key_id,
            ]);

            if ($mapping && $mapping->key_id) {
                $cityName = \App\Models\City::where('id', $mapping->key_id)->value('City_Name');
                if ($cityName) return $cityName;
            }
        } catch (\Exception $e) {
            Log::warning('getCompanyCityName failed', [
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
                'error'          => $e->getMessage(),
            ]);
        }

        // Fallback — direct city lookup by the raw Source_ID/Destination_ID
        return \App\Models\City::where('id', $globalCityId)->value('City_Name') ?? 'Unknown';
    }
}