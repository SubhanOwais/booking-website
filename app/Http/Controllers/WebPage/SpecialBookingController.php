<?php

namespace App\Http\Controllers\WebPage;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Company;
use App\Models\SpecialBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SpecialBookingController extends Controller
{
    private const BUS_TYPES = [
        [
            'value'       => 'executive_class',
            'label'       => 'Executive Class',
            'description' => 'Comfortable seats, AC, ideal for group travel',
            'capacity'    => '48 seats',
        ],
        [
            'value'       => 'premium_business_9x32',
            'label'       => 'Premium Business 9X32',
            'description' => 'Premium reclining seats, WiFi & onboard entertainment',
            'capacity'    => '41 seats',
        ],
        [
            'value'       => 'premium_business_12x28',
            'label'       => 'Premium Business 12X28',
            'description' => 'Perfect for small groups and corporate transfers',
            'capacity'    => '40 berths',
        ],
        [
            'value'       => 'ac_sleeper',
            'label'       => 'AC SLEEPER',
            'description' => 'Full-flat beds for overnight long-distance journeys',
            'capacity'    => '34 seats',
        ],
    ];

    // ── Render page ───────────────────────────────────────────────────────

    /**
     * GET /special-booking
     */
    public function index(): Response
    {
        $cities = City::orderBy('City_Name')
            ->get(['City_Id', 'City_Name', 'City_Abbr', 'Active']);

        $companies = Company::where('company_type', 'bus')
            ->where('is_active', true)
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'company_logo', 'city'])
            ->map(fn($c) => [
                'id'       => $c->id,
                'name'     => $c->company_name,
                'city'     => $c->city,
                'logo_url' => $c->logo_url,
            ]);

        return Inertia::render('Landing/SpecialBooking/index', [
            'cities'    => $cities,
            'companies' => $companies,
            'busTypes'  => self::BUS_TYPES,
        ]);
    }

    // ── Store booking ─────────────────────────────────────────────────────

    /**
     * POST /special-booking
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'passenger_name'  => 'required|string|max:100',
            'passenger_phone' => 'required|string|max:20',
            'passenger_email' => 'nullable|email|max:150',
            'from_city_id'    => 'required|integer|exists:cities,City_Id',
            'to_city_id'      => 'required|integer|exists:cities,City_Id|different:from_city_id',
            'travel_date'     => 'required|date|after_or_equal:today',
            'preferred_time'  => 'nullable|date_format:H:i',
            'send_to_all'     => 'boolean',
            'company_ids'     => 'nullable|array',
            'company_ids.*'   => 'integer|exists:companies,id',
            'bus_type'        => 'required|in:standard,luxury,sleeper,mini_coach,double_decker',
            'special_notes'   => 'nullable|string|max:1000',
        ], [
            'to_city_id.different'       => 'Destination city must be different from origin city.',
            'travel_date.after_or_equal' => 'Travel date cannot be in the past.',
        ]);

        // Resolve which company IDs to fan out to
        if ($validated['send_to_all'] ?? false) {
            $companyIds = Company::where('company_type', 'bus')
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        } else {
            $companyIds = $validated['company_ids'] ?? [];
        }

        // If none selected, save one record with no company
        if (empty($companyIds)) {
            $companyIds = [null];
        }

        $baseData = collect($validated)
            ->except(['send_to_all', 'company_ids'])
            ->merge([
                'status'     => 'pending',
                'created_by' => Auth::id(),
            ])
            ->toArray();

        $references = [];

        foreach ($companyIds as $companyId) {
            $booking = SpecialBooking::create(array_merge($baseData, [
                'company_id' => $companyId,
            ]));

            $references[] = 'SB-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
        }

        return redirect()->back()->with('success', [
            'message'    => count($references) > 1
                ? 'Your request has been sent to ' . count($references) . ' companies. We will contact you shortly.'
                : 'Your special booking request has been submitted successfully! We will contact you shortly.',
            'references' => $references,
        ]);
    }

    // ── Admin (API) ───────────────────────────────────────────────────────

    public function adminIndex(Request $request): JsonResponse
    {
        $bookings = SpecialBooking::with(['fromCity', 'toCity', 'company'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date,   fn($q) => $q->byDate($request->date))
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    public function updateStatus(Request $request, SpecialBooking $specialBooking): JsonResponse
    {
        $request->validate([
            'status'       => 'required|in:pending,confirmed,cancelled,completed',
            'quoted_price' => 'nullable|numeric|min:0',
        ]);

        $specialBooking->update($request->only('status', 'quoted_price'));

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated.',
            'booking' => $specialBooking->fresh(),
        ]);
    }
}
