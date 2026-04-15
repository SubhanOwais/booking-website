<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use App\Models\Discount;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

class CompanyDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->Company_Id;
        $now = Carbon::now();

        // ── Revenue Stats ────────────────────────────────────────────────
        $last24hAmount = TicketingSeat::where('Status', 'Confirmed')
            ->where('Company_Id', $companyId)
            ->where('PaymentDate', '>=', $now->copy()->subHours(24))
            ->sum('Fare');

        $last7dAmount = TicketingSeat::where('Status', 'Confirmed')
            ->where('Company_Id', $companyId)
            ->where('PaymentDate', '>=', $now->copy()->subDays(7))
            ->sum('Fare');

        $last30dAmount = TicketingSeat::where('Status', 'Confirmed')
            ->where('Company_Id', $companyId)
            ->where('PaymentDate', '>=', $now->copy()->subDays(30))
            ->sum('Fare');

        // ── Ticket Counts ───────────────────────────────────────────────
        $totalBookings = TicketingSeat::where('Status', 'Confirmed')
            ->where('Company_Id', $companyId)
            ->count();

        $todayBookings = TicketingSeat::where('Status', 'Confirmed')
            ->where('Company_Id', $companyId)
            ->whereDate('PaymentDate', $now->toDateString())
            ->count();

        $pendingBookings = TicketingSeat::where('Status', 'Pending')
            ->where('Company_Id', $companyId)
            ->count();

        // ── Latest 10 Tickets ───────────────────────────────────────────
        $latestTickets = TicketingSeat::with(['fromCity', 'toCity'])
            ->where('Status', 'Confirmed')
            ->where('Company_Id', $companyId)
            ->orderBy('PaymentDate', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($ticket) use ($now) {
                return [
                    'id'           => $ticket->id,
                    'pnr'          => $ticket->PNR_No,
                    'passenger'    => $ticket->Passenger_Name,
                    'contact'      => $ticket->Contact_No,
                    'from'         => optional($ticket->fromCity)->City_Name ?? $ticket->Source_ID,
                    'to'           => optional($ticket->toCity)->City_Name ?? $ticket->Destination_ID,
                    'travel_date'  => $ticket->Travel_Date
                        ? Carbon::parse($ticket->Travel_Date)->format('d M, Y')
                        : '—',
                    'travel_time'  => $ticket->Travel_Time,
                    'fare'         => number_format($ticket->Fare, 0),
                    'seat_no'      => $ticket->Seat_No,
                    'bus_service'  => $ticket->Bus_Service,
                    'payment_date' => $ticket->PaymentDate
                        ? Carbon::parse($ticket->PaymentDate)->format('d M, Y H:i')
                        : '—',
                    'gender'       => $ticket->Gender,
                    'status'       => $ticket->Status,
                ];
            });

        // ── Active Discounts for this company ───────────────────────────
        $activeDiscounts = Discount::active()
            ->forCompany($companyId)
            ->with('mainCity')
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function ($discount) use ($now) {
                $endsAt = $discount->end_date;
                $secondsLeft = $endsAt ? max(0, $now->diffInSeconds($endsAt, false)) : null;

                return [
                    'id'                  => $discount->id,
                    'name'                => $discount->name,
                    'discount_percentage' => $discount->discount_percentage,
                    'main_city'           => optional($discount->mainCity)->City_Name ?? '—',
                    'company_names'       => $discount->company_names,
                    'mapped_city_count'   => count($discount->mapped_city_ids ?? []),
                    'start_date'          => $discount->start_date
                        ? Carbon::parse($discount->start_date)->format('d M, Y')
                        : '—',
                    'end_date'            => $endsAt
                        ? $endsAt->format('d M, Y H:i')
                        : '—',
                    'ends_at_iso'         => $endsAt?->toISOString(),
                    'seconds_left'        => $secondsLeft,
                    'status'              => $discount->status,
                ];
            });

        // dd($activeDiscounts, $latestTickets, $last24hAmount, $last7dAmount, $last30dAmount, $totalBookings, $todayBookings, $pendingBookings);

        return Inertia::render('Company/Dashboard/Index', [
            'stats' => [
                'last24hAmount'   => number_format($last24hAmount, 0),
                'last7dAmount'    => number_format($last7dAmount, 0),
                'last30dAmount'   => number_format($last30dAmount, 0),
                'totalBookings'   => $totalBookings,
                'todayBookings'   => $todayBookings,
                'pendingBookings' => $pendingBookings,
            ],
            'latestTickets'   => $latestTickets,
            'activeDiscounts' => $activeDiscounts,
            'user'            => [
                'id'       => $user->id,
                'name'     => $user->Full_Name,
                'email'    => $user->Email,
                'phone'    => $user->Phone_Number,
                'userType' => $user->User_Type,
            ],
        ]);
    }


    public function companyData(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->Company_Id;

        $company = Company::with(['owner', 'users'])->findOrFail($companyId);

        // dd($company);
        $owner = $company->users->where('User_Type', 'CompanyOwner')->first();
        $otherUsers = $company->users->where('id', '!=', $owner?->id)->values();

        // If the request expects JSON (e.g., from axios), return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'company' => $company,
                'owner'   => $owner,
                'users'   => $otherUsers,
            ]);
        }

        // Otherwise, render the Inertia page (if you plan to use this route for the page)
        return Inertia::render('Company/Data', [
            'company' => $company,
            'owner'   => $owner,
            'users'   => $otherUsers,
        ]);
    }
}
