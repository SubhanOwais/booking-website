<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers\WebPage;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Gallery;
use App\Models\Discount;
use App\Models\City;
use App\Models\TicketingSeat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // Fetch ALL tickets except pure Pending
        $tickets = TicketingSeat::with(['fromCity', 'toCity'])
            ->where('Customer_Id', $userId)
            ->where('Status', '!=', 'Pending')
            ->orderBy('Travel_Date', 'desc')
            ->orderBy('Travel_Time', 'desc')
            ->get();

        // Initialize arrays for each category
        $activeJourneys    = [];
        $cancelledJourneys = [];
        $refundJourneys    = [];

        foreach ($tickets as $ticket) {
            // Cast values safely
            $fare         = (float) ($ticket->Fare ?? 0);
            $originalFare = (float) ($ticket->Original_Fare ?? $ticket->Fare ?? 0);
            $discount     = (float) ($ticket->Discount ?? 0);

            if ($originalFare == 0 && $fare > 0) {
                $originalFare = $fare + $discount;
            }

            // Determine which array to add to based on status
            if ($ticket->Status === 'Cancelled') {
                $targetArray = &$cancelledJourneys;
            } elseif ($ticket->Status === 'Pending Refund') {
                $targetArray = &$refundJourneys;
            } else {
                $targetArray = &$activeJourneys;
            }

            // Base PNR
            $basePnr = str_contains($ticket->PNR_No, '-')
                ? explode('-', $ticket->PNR_No)[0]
                : $ticket->PNR_No;

            $journeyKey = implode('_', [
                $basePnr,
                $ticket->Travel_Date,
                $ticket->Source_ID,
                $ticket->Destination_ID,
                $ticket->Status,
            ]);

            if (!isset($targetArray[$journeyKey])) {
                $travelDateTime = Carbon::parse($ticket->Travel_Date)
                    ->setTimeFromTimeString($ticket->Travel_Time);

                // ✅ Get company-specific city names using mapping
                $operatorId = (int) $ticket->Company_Id;
                $sourceCityName = $this->getCompanyCityName($operatorId, $ticket->Source_ID);
                $destCityName   = $this->getCompanyCityName($operatorId, $ticket->Destination_ID);

                // ✅ Get company info from DB
                $company = \App\Models\Company::where('company_id', $operatorId)
                    ->where('is_active', true)
                    ->first();

                $targetArray[$journeyKey] = [
                    'base_pnr'        => $basePnr,
                    'from'            => $sourceCityName,
                    'to'              => $destCityName,
                    'date'            => Carbon::parse($ticket->Travel_Date)->format('M d, Y'),
                    'time'            => $ticket->Travel_Time
                                            ? Carbon::parse($ticket->Travel_Time)->format('h:i A')
                                            : 'N/A',
                    'travel_datetime' => $travelDateTime,
                    'passenger_name'  => $ticket->Passenger_Name,
                    'company_name'    => $company?->company_name ?? $ticket->Company_Name,
                    'logo'            => $company?->logo_url ?? asset('images/logo.jpg'),
                    'helpline'        => $company?->helpline_number ?? 'N/A',

                    // Passenger Information
                    'CNIC'              => $ticket->CNIC,
                    'Gender'            => $ticket->Gender,
                    'Contact_No'        => $ticket->Contact_No,
                    'Emergency_Contact' => $ticket->Emergency_Contact,

                    // Booking Information
                    'Issue_Date'        => $ticket->Issue_Date,
                    'Issued_By'         => $ticket->Issued_By ?? null,
                    'Boarding_Terminal' => $ticket->Boarding_Terminal,
                    'CollectionPoint'   => $ticket->CollectionPoint,
                    'Invoice'           => $ticket->Invoice ?? null,

                    // Journey Information
                    'Travel_Time'    => $ticket->Travel_Time,
                    'Source_ID'      => $ticket->Source_ID,
                    'Destination_ID' => $ticket->Destination_ID,

                    // Payment Information
                    'PaymentDate' => $ticket->PaymentDate ?? null,

                    // Totals
                    'total_price'         => 0,
                    'total_seats'         => 0,
                    'total_original_fare' => 0,
                    'total_discount'      => 0,

                    // Seat-level
                    'seat_numbers'  => [],
                    'ticket_details' => [],
                    'full_pnrs'     => [],
                    'all_statuses'  => [],
                ];
            }

            // Accumulate totals
            $targetArray[$journeyKey]['total_price']         += $fare;
            $targetArray[$journeyKey]['total_original_fare'] += $originalFare;
            $targetArray[$journeyKey]['total_discount']      += $discount;
            $targetArray[$journeyKey]['total_seats']++;

            $targetArray[$journeyKey]['seat_numbers'][] = (string) $ticket->Seat_No;
            $targetArray[$journeyKey]['full_pnrs'][]    = $ticket->PNR_No;
            $targetArray[$journeyKey]['all_statuses'][] = $ticket->Status;

            $targetArray[$journeyKey]['ticket_details'][] = [
                'id'               => $ticket->id,
                'pnr'              => $ticket->PNR_No,
                'seat_no'          => $ticket->Seat_No,
                'fare'             => $fare,
                'original_fare'    => $originalFare,
                'discount'         => $discount,
                'status'           => $ticket->Status,
                'CNIC'             => $ticket->CNIC,
                'Gender'           => $ticket->Gender,
                'Contact_No'       => $ticket->Contact_No,
                'Emergency_Contact'=> $ticket->Emergency_Contact,
                'Issue_Date'       => $ticket->Issue_Date,
                'Boarding_Terminal'=> $ticket->Boarding_Terminal,
                'CollectionPoint'  => $ticket->CollectionPoint,
            ];
        }

        // Process journeys function
        $processJourneys = function ($journeys) {
            $processed = [];
            foreach ($journeys as $journeyKey => $journey) {
                sort($journey['seat_numbers']);

                $journey['seats_display']     = implode(', ', $journey['seat_numbers']);
                $journey['base_pnr_display']  = $journey['base_pnr'];
                $journey['full_pnrs_display'] = implode(', ', array_unique($journey['full_pnrs']));

                $journey['total_price']         = round($journey['total_price'], 2);
                $journey['total_original_fare'] = round($journey['total_original_fare'], 2);
                $journey['total_discount']      = round($journey['total_discount'], 2);

                $statuses = array_unique($journey['all_statuses']);

                if (count($statuses) === 1 && $statuses[0] === 'Cancelled') {
                    $journey['status'] = 'Cancelled';
                } elseif (count($statuses) === 1 && $statuses[0] === 'Pending Refund') {
                    $journey['status'] = 'Pending Refund';
                } else {
                    $journey['status'] = 'Confirmed';
                    $journey['seat_statuses'] = [];
                    foreach ($journey['ticket_details'] as $ticket) {
                        $journey['seat_statuses'][$ticket['seat_no']] = $ticket['status'];
                    }
                }

                $processed[] = $journey;
            }

            usort($processed, fn ($a, $b) => $b['travel_datetime'] <=> $a['travel_datetime']);
            return $processed;
        };

        $activeJourneys    = $processJourneys($activeJourneys);
        $cancelledJourneys = $processJourneys($cancelledJourneys);
        $refundJourneys    = $processJourneys($refundJourneys);

        // Split active journeys into upcoming and history
        $now = Carbon::now();
        $upcomingTrips = [];
        $travelHistory = [];

        foreach ($activeJourneys as $journey) {
            if ($journey['travel_datetime']->isFuture()) {
                $upcomingTrips[] = $journey;
            } else {
                $travelHistory[] = $journey;
            }
        }

        $cancelledHistory = array_merge($cancelledJourneys, $refundJourneys);

        // Customer info
        $memberSince = $user->Created_On
            ? Carbon::parse($user->Created_On)->format('M d, Y')
            : 'N/A';

        $customer = [
            'Full_Name'         => $user->Full_Name,
            'Email'             => $user->Email,
            'Phone_Number'      => $user->Phone_Number,
            'Emergency_Number'  => $user->Emergency_Number ?? null,
            'CNIC'              => $user->CNIC ?? null,
            'Address'           => $user->Address ?? null,
            'profile_picture_url' => $user->Profile_Picture
                ? (
                    filter_var($user->Profile_Picture, FILTER_VALIDATE_URL)
                        ? $user->Profile_Picture
                        : (
                            str_starts_with($user->Profile_Picture, 'images/')
                            || str_starts_with($user->Profile_Picture, 'uploads/')
                            || str_starts_with($user->Profile_Picture, 'profiles/')
                                ? asset($user->Profile_Picture)
                                : asset('storage/' . ltrim($user->Profile_Picture, '/'))
                        )
                )
                : asset('images/logo.jpg'),
            'is_email_verified' => !is_null($user->email_verified_at),
            'MemberSince'       => $memberSince,
        ];

        // Stats
        $totalSpent = round(array_reduce($activeJourneys, fn($carry, $j) => $carry + $j['total_price'], 0), 2);
        $totalSaved = round(array_reduce($activeJourneys, fn($carry, $j) => $carry + $j['total_discount'], 0), 2);

        $cancelledPNRs = array_unique(array_column($cancelledJourneys, 'base_pnr'));
        $refundPNRs    = array_unique(array_column($refundJourneys, 'base_pnr'));

        $stats = [
            'upcomingTrips' => count($upcomingTrips),
            'cancelled'     => count($cancelledPNRs) + count($refundPNRs),
            'totalTrips'    => count($travelHistory),
            'totalSpent'    => $totalSpent,
            'totalSaved'    => $totalSaved,
        ];

        return Inertia::render('Profile/Sections/ProfilePage', [
            'customer'        => $customer,
            'stats'           => $stats,
            'upcomingTrips'   => $upcomingTrips,
            'travelHistory'   => $travelHistory,
            'cancelledHistory'=> $cancelledHistory,
        ]);
    }

    /**
     * Get company-specific city name using CompanyCity mapping
     */
    private function getCompanyCityName(int $operatorId, int $globalCityId): string
    {
        try {
            // Look up mapping: company_id = operator_id, key_id = global city ID
            $mapping = \App\Models\CompanyCity::where('company_id', $operatorId)
                ->where('city_id', $globalCityId)
                ->where('active', true)
                ->first();

            if ($mapping && $mapping->city_id) {
                // Get city name using the company-specific city_id
                $cityName = \App\Models\City::where('id', $mapping->key_id)->value('City_Name');
                if ($cityName) {
                    return $cityName;
                }
            }
        } catch (\Exception $e) {
            Log::warning('getCompanyCityName failed', [
                'operator_id' => $operatorId,
                'global_city_id' => $globalCityId,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to global city name
        return \App\Models\City::where('id', $globalCityId)->value('City_Name') ?? 'Unknown';
    }

    /**
     * Cancel booking and set status to "Pending Refund" for specific seats
     */
    public function cancelBooking(Request $request)
    {
        try {
            $validated = $request->validate([
                'base_pnr' => 'required|string',
                'seat_numbers' => 'required|array|min:1',
                'seat_numbers.*' => 'string',
                'reason' => 'required|string|max:500',
            ]);

            $updatedCount = 0;
            $updatedBookings = [];

            // Get all bookings with this base PNR
            $bookings = TicketingSeat::where('PNR_No', 'LIKE', $validated['base_pnr'] . '-%')->get();

            if ($bookings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No bookings found for PNR: ' . $validated['base_pnr']
                ], 404);
            }

            // For debugging - show what bookings were found
            $foundBookings = [];
            foreach ($bookings as $booking) {
                $foundBookings[] = [
                    'full_pnr' => $booking->PNR_No,
                    'seat_no' => $booking->Seat_No,
                    'status' => $booking->Status
                ];
            }

            // Update only the specified seats
            foreach ($bookings as $booking) {
                // Check if this booking's seat number is in our selected list
                if (in_array($booking->Seat_No, $validated['seat_numbers'])) {
                    $booking->Status = 'Pending Refund';
                    $booking->Refund_Reason = $validated['reason'];
                    $booking->save();

                    $updatedCount++;
                    $updatedBookings[] = $booking->PNR_No;
                }
            }

            if ($updatedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching seats found. Available seats: ' . implode(', ', array_column($foundBookings, 'seat_no')),
                    'debug' => [
                        'base_pnr' => $validated['base_pnr'],
                        'requested_seats' => $validated['seat_numbers'],
                        'available_seats' => $foundBookings
                    ]
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => $updatedCount . ' seat(s) cancelled successfully',
                'data' => [
                    'updated_count' => $updatedCount,
                    'cancelled_seats' => $validated['seat_numbers'],
                    'updated_pnrs' => $updatedBookings
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Manual email validation
        $emailRules = ['required', 'email', 'max:255'];

        // Only check uniqueness if email is changing
        if ($request->Email !== $user->Email) {
            $emailRules[] = 'unique:users,Email';
        }

        $validator = Validator::make($request->all(), [
            'Full_Name' => 'required|string|max:255',
            'Email' => $emailRules,
            'Phone_Number' => 'nullable|string|max:20',
            'CNIC' => 'nullable|string|size:13',
            'Emergency_Number' => 'nullable|string|max:20',
            'Address' => 'nullable|string|max:500',
            'Profile_Picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'Profile_Picture.max' => 'The profile picture must not exceed 2MB.',
            'Profile_Picture.image' => 'The file must be an image.',
            'Email.unique' => 'This email is already taken.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update all fields (only if they exist in request)
            $user->Full_Name = $request->Full_Name;
            $user->Email = $request->Email;

            // Update optional fields only if they are provided
            if ($request->has('Phone_Number')) {
                $user->Phone_Number = $request->Phone_Number;
            }

            if ($request->has('CNIC')) {
                $user->CNIC = $request->CNIC;
            }

            if ($request->has('Emergency_Number')) {
                $user->Emergency_Number = $request->Emergency_Number;
            }

            if ($request->has('Address')) {
                $user->Address = $request->Address;
            }

            // Handle profile picture removal
            if ($request->has('remove_profile_picture') && $request->boolean('remove_profile_picture')) {
                if ($user->Profile_Picture && Storage::disk('public')->exists($user->Profile_Picture)) {
                    Storage::disk('public')->delete($user->Profile_Picture);
                }
                $user->Profile_Picture = null;
            }

            // Handle new profile picture upload
            if ($request->hasFile('Profile_Picture')) {
                if ($user->Profile_Picture && Storage::disk('public')->exists($user->Profile_Picture)) {
                    Storage::disk('public')->delete($user->Profile_Picture);
                }
                $path = $request->file('Profile_Picture')->store('profile-pictures', 'public');
                $user->Profile_Picture = $path;
            }

            $user->Changed_On = now();
            $user->save();

            $updatedUser = $this->formatUserData($user);

            return response()->json([
                'success' => 'Profile updated successfully!',
                'customer' => $updatedUser,
            ]);

        } catch (\Exception $e) {
            \Log::error('Profile update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.'
            ], 500);
        }
    }

    /**
     * Format user data for response
     */
    private function formatUserData($user)
    {
        return [
            'id' => $user->id,
            'User_ID' => $user->User_ID ?? $user->id,
            'Full_Name' => $user->Full_Name,
            'User_Name' => $user->User_Name,
            'Email' => $user->Email,
            'Phone_Number' => $user->Phone_Number,
            'Address' => $user->Address,
            'CNIC' => $user->CNIC,
            'Emergency_Number' => $user->Emergency_Number,
            'Profile_Picture' => $user->Profile_Picture,
            'profile_picture_url' => $user->Profile_Picture
                ? asset('storage/' . $user->Profile_Picture)
                : asset('images/logo.jpg'),
            'IsSuperAdmin' => $user->IsSuperAdmin,
            'IsAdmin' => $user->IsAdmin,
            'Created_On' => $user->Created_On,
            'Changed_On' => $user->Changed_On,
            'User_Type' => $user->User_Type,
            'updated_at' => $user->updated_at,
        ];
    }

    // NEW METHOD: Get all active discounts for frontend display
    public function getAllDiscounts()
    {
        $discounts = Discount::with('mainCity')
            ->active()
            ->latest()
            ->get()
            ->map(function ($discount) {
                $mappedCities = [];
                if ($discount->mapped_city_ids) {
                    $mappedCities = City::whereIn('City_Id', $discount->mapped_city_ids)
                        ->pluck('City_Name')
                        ->toArray();
                }

                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'discount_percentage' => $discount->discount_percentage,
                    'main_city_id' => $discount->main_city_id,
                    'main_city_name' => $discount->mainCity->City_Name ?? 'N/A',
                    'mapped_city_ids' => $discount->mapped_city_ids ?? [],
                    'mapped_city_names_array' => $mappedCities,
                    'company_names' => $discount->company_names,
                    'is_active' => $discount->is_active,
                    'status' => $discount->status,
                    'is_valid' => $discount->is_valid,
                    'start_date' => $discount->start_date?->format('Y-m-d H:i'),
                    'end_date' => $discount->end_date?->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'discounts' => $discounts
        ]);
    }
}
