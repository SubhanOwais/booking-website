<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\TicketingSeat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $now = Carbon::now();

        // ── Revenue Stats ─────────────────────────────────────────────────────
        $last24hAmount = TicketingSeat::where('Status', 'Confirmed')
            ->where('PaymentDate', '>=', $now->copy()->subHours(24))
            ->sum('Fare');

        $last7dAmount = TicketingSeat::where('Status', 'Confirmed')
            ->where('PaymentDate', '>=', $now->copy()->subDays(7))
            ->sum('Fare');

        $last30dAmount = TicketingSeat::where('Status', 'Confirmed')
            ->where('PaymentDate', '>=', $now->copy()->subDays(30))
            ->sum('Fare');

        // ── Ticket Counts ─────────────────────────────────────────────────────
        $totalBookings    = TicketingSeat::where('Status', 'Confirmed')->count();
        $todayBookings    = TicketingSeat::where('Status', 'Confirmed')
            ->whereDate('PaymentDate', $now->toDateString())
            ->count();
        $pendingBookings  = TicketingSeat::where('Status', 'Pending')->count();

        // ── Latest 10 Tickets ─────────────────────────────────────────────────
        $latestTickets = TicketingSeat::with(['fromCity', 'toCity'])
            ->where('Status', 'Confirmed')
            ->orderBy('PaymentDate', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id'             => $ticket->id,
                    'pnr'            => $ticket->PNR_No,
                    'passenger'      => $ticket->Passenger_Name,
                    'contact'        => $ticket->Contact_No,
                    'from'           => optional($ticket->fromCity)->City_Name ?? $ticket->Source_ID,
                    'to'             => optional($ticket->toCity)->City_Name ?? $ticket->Destination_ID,
                    'travel_date'    => $ticket->Travel_Date
                        ? Carbon::parse($ticket->Travel_Date)->format('d M, Y')
                        : '—',
                    'travel_time'    => $ticket->Travel_Time,
                    'fare'           => number_format($ticket->Fare, 0),
                    'seat_no'        => $ticket->Seat_No,
                    'bus_service' => $this->getServiceTypeName($ticket->Bus_Service),
                    'payment_date'   => $ticket->PaymentDate
                        ? Carbon::parse($ticket->PaymentDate)->format('d M, Y H:i')
                        : '—',
                    'gender'         => $ticket->Gender,
                    'status'         => $ticket->Status,
                ];
            });

        // ── Active Discounts (with time remaining) ────────────────────────────
        $activeDiscounts = Discount::active()
            ->with('mainCity')
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function ($discount) use ($now) {
                $endsAt        = $discount->end_date ? Carbon::parse($discount->end_date) : null;
                $secondsLeft   = $endsAt ? max(0, $now->diffInSeconds($endsAt, false)) : null;

                return [
                    'id'                 => $discount->id,
                    'name'               => $discount->name,
                    'discount_percentage' => $discount->discount_percentage,
                    'main_city'          => optional($discount->mainCity)->City_Name ?? '—',
                    'company_names'      => $discount->company_names ?? [],
                    'mapped_city_count'  => count($discount->mapped_city_ids ?? []),
                    'start_date'         => $discount->start_date
                        ? Carbon::parse($discount->start_date)->format('d M, Y')
                        : '—',
                    'end_date'           => $endsAt
                        ? $endsAt->format('d M, Y H:i')
                        : '—',
                    'ends_at_iso'        => $endsAt?->toISOString(),   // for JS countdown
                    'seconds_left'       => $secondsLeft,
                    'status'             => $discount->status,
                ];
            });

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                'last24hAmount'  => number_format($last24hAmount, 0),
                'last7dAmount'   => number_format($last7dAmount, 0),
                'last30dAmount'  => number_format($last30dAmount, 0),
                'totalBookings'  => $totalBookings,
                'todayBookings'  => $todayBookings,
                'pendingBookings' => $pendingBookings,
            ],
            'latestTickets'  => $latestTickets,
            'activeDiscounts' => $activeDiscounts,
        ]);
    }

    /**
     * Get service type name
     */
    private function getServiceTypeName($serviceTypeId)
    {
        $services = [
            1  => 'LUXURY',
            2  => 'Daewoo',
            3  => 'NONAC 65',
            4  => 'HINO',
            5  => 'HIGH ROOF 14',
            6  => 'HIGH ROOF 18',
            7  => 'LOCAL DAEWOO',
            8  => 'SUPER LUXURY',
            13 => 'Executive',
            14 => 'Executive Plus',
            15 => 'AC SLEEPER',
            16 => 'Executive Plus 41',
            17 => 'Premium Business',
            18 => 'Premium Business 12X28',
            19 => 'Premium Business 9X32',
        ];

        return $services[$serviceTypeId] ?? 'N/A';
    }
}
