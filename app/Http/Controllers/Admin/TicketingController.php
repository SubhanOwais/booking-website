<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketingController extends Controller
{
    public function index(Request $request)
    {
        $query = TicketingSeat::with(['fromCity', 'toCity', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('Status', $request->status);
        }

        // Filter by travel date range
        if ($request->filled('date_from')) {
            $query->whereDate('Travel_Date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('Travel_Date', '<=', $request->date_to);
        }

        // Filter by booking date range (created_at)
        if ($request->filled('booked_from')) {
            $query->whereDate('created_at', '>=', $request->booked_from);
        }
        if ($request->filled('booked_to')) {
            $query->whereDate('created_at', '<=', $request->booked_to);
        }

        // Search by PNR, passenger name, or contact
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No', 'like', "%{$search}%")
                    ->orWhere('Passenger_Name', 'like', "%{$search}%")
                    ->orWhere('Contact_No', 'like', "%{$search}%");
            });
        }

        // Filter by company
        if ($request->filled('company')) {
            $query->where('Company_Name', $request->company);
        }

        $tickets = $query->paginate(50)->withQueryString();

        // Get statistics
        $stats = [
            'total' => TicketingSeat::count(),
            'pending' => TicketingSeat::where('Status', 'Pending')->count(),
            'confirmed' => TicketingSeat::where('Status', 'Confirmed')->count(),
            'cancelled' => TicketingSeat::where('Status', 'Cancelled')->count(),
        ];

        // Get unique companies for filter
        $companies = TicketingSeat::distinct()
            ->whereNotNull('Company_Name')
            ->pluck('Company_Name')
            ->filter()
            ->values();

        return Inertia::render('Admin/Ticketing/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'companies' => $companies,
            'filters' => [
                'status'      => $request->status ?? '',
                'date_from'   => $request->date_from ?? '',
                'date_to'     => $request->date_to ?? '',
                'booked_from' => $request->booked_from ?? '',
                'booked_to'   => $request->booked_to ?? '',
                'search'      => $request->search ?? '',
                'company'     => $request->company ?? '',
            ]
        ]);
    }

    public function show($id)
    {
        $ticket = TicketingSeat::with(['fromCity', 'toCity', 'user'])
            ->findOrFail($id);

        // Return JSON for modal fetch requests
        if (request()->expectsJson()) {
            return response()->json(['ticket' => $ticket]);
        }

        return Inertia::render('Admin/Ticketing/Show', [
            'ticket' => $ticket
        ]);
    }

    public function export(Request $request)
    {
        $query = TicketingSeat::with(['fromCity', 'toCity']);

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

        $filename = 'tickets_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');

            // Headers
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
                'Issue Date'
            ]);

            // Data
            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->PNR_No,
                    $ticket->Passenger_Name,
                    $ticket->Contact_No,
                    $ticket->fromCity?->City_Name ?? 'N/A',
                    $ticket->toCity?->City_Name ?? 'N/A',
                    $ticket->Travel_Date,
                    $ticket->Travel_Time,
                    is_array($ticket->seatNumbers) ? implode(', ', $ticket->seatNumbers) : $ticket->Seat_No,
                    $ticket->Fare,
                    $ticket->Status,
                    $ticket->Company_Name,
                    $ticket->Issue_Date?->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
