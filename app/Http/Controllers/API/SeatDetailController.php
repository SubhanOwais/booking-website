<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Discount;
use App\Models\CompanyCity;
use App\Models\TicketingSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Inertia\Inertia;

class SeatDetailController extends Controller
{
    // ✅ Keyed by operator_id — credentials never exposed to frontend
    private array $companies = [];

    public function __construct()
    {
        $this->companies = array_values(config('companies.bus', []));
    }

    // =========================================================================
    // ✅ Find company config by operator_id — never by name
    // =========================================================================
    private function findCompanyByOperatorId(string $operatorId): ?array
    {
        $config = collect($this->companies)
            ->firstWhere('operator_id', (string) $operatorId);

        if (!$config) return null;

        // Load name/logo from DB
        $dbCompany = Company::where('company_id', (int) $operatorId)
            ->where('is_active', true)
            ->first(['company_id', 'company_name', 'company_logo']);

        if (!$dbCompany) return null;

        $config['name'] = $dbCompany->company_name;
        $config['logo'] = $dbCompany->logo_url;

        return $config;
    }

    // =========================================================================
    // index — render seat detail page
    // =========================================================================
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date'                => 'required|date',
            'from'                => 'required|string',
            'to'                  => 'required|string',
            'serviceTypeId'       => 'required|string',
            'departureTime'       => 'required|string',
            'scheduleId'          => 'nullable|string',
            'price'               => 'required|numeric',
            'seatsAvailable'      => 'required|integer',
            'busType'             => 'nullable|string',
            'busService'          => 'required|string',
            'company'             => 'required|string',
            'operator_id'         => 'required|string',
            'fromId'              => 'nullable|integer',
            'toId'                => 'nullable|integer',
            'companylogo'         => 'nullable|string',
            'total_fare'          => 'nullable|numeric',
            'seat_20_fare'        => 'nullable|numeric',
            'seat_4_fare'         => 'nullable|numeric',
            'extra_fare'          => 'nullable|numeric',
            'discount_percentage' => 'nullable|numeric',
            'discounted_price'    => 'nullable|numeric',
        ]);

        $dbCompany = Company::where('company_id', (int) $validated['operator_id'])
            ->where('is_active', true)
            ->first(['company_id', 'company_name', 'company_logo']);

        return Inertia::render('Landing/SeatDetailPage/Index', [
            'canLogin' => \Route::has('login'),
            'tripData' => [
                'date'                => $validated['date'],
                'from'                => $validated['from'],
                'to'                  => $validated['to'],
                'serviceTypeId'       => $validated['serviceTypeId'],
                'departureTime'       => $validated['departureTime'],
                'scheduleId'          => $validated['scheduleId']          ?? null,
                'price'               => (float) $validated['price'],
                'seatsAvailable'      => (int)   $validated['seatsAvailable'],
                'busType'             => $validated['busType']             ?? '',
                'busService'          => $validated['busService'],
                'fromId'              => (int) ($validated['fromId']       ?? 0),
                'toId'                => (int) ($validated['toId']         ?? 0),
                'company'             => $dbCompany?->company_name         ?? $validated['company'],
                'operator_id'         => $validated['operator_id'],
                'companylogo'         => $dbCompany?->logo_url             ?? $validated['companylogo'] ?? '',
                'total_fare'          => (float) ($validated['total_fare']          ?? $validated['price']),
                'seat_20_fare'        => (float) ($validated['seat_20_fare']        ?? $validated['price']),
                'seat_4_fare'         => (float) ($validated['seat_4_fare']         ?? $validated['price']),
                'extra_fare'          => (float) ($validated['extra_fare']          ?? 0),
                'discount_percentage' => (float) ($validated['discount_percentage'] ?? 0),
                'discounted_price'    => (float) ($validated['discounted_price']    ?? $validated['price']),
                'has_discount'        => ((float) ($validated['discount_percentage'] ?? 0)) > 0,
                'formattedPrice'      => number_format($validated['price'], 0) . ' PKR',
                'travelDate'          => date('d M, Y', strtotime($validated['date'])),
            ],
            'pageTitle' => 'Select Seats - ' . $validated['from'] . ' to ' . $validated['to'],
        ]);
    }

    // =========================================================================
    // createBooking
    // =========================================================================
    public function createBooking(Request $request)
    {
        try {
            $validated = $request->validate([
                'fullName'                       => 'required|string|max:255',
                'email'                          => 'required|email|max:255',
                'phone'                          => 'required|string|max:20',
                'emergencyContact'               => 'nullable|max:20',
                'cnic'                           => 'required|string|size:13',
                'gender'                         => 'required|in:male,female',
                'pickupTerminal'                 => 'nullable|string',
                'pickupTerminalId'               => 'required|string',
                'selectedSeats'                  => 'required|array|min:1',
                'selectedSeats.*.seatId'         => 'required|integer',
                'selectedSeats.*.label'          => 'required|string',
                'selectedSeats.*.fare'           => 'required|numeric|min:0',
                'selectedSeats.*.original_fare'  => 'required|numeric|min:0',
                'selectedSeats.*.discount'       => 'required|numeric|min:0',
                'selectedSeats.*.gender'         => 'required|in:male,female',
                'scheduleId'                     => 'required|string',
                'companyName'                    => 'required|string',
                'operator_id'                    => 'required|string',
                'sourceId'                       => 'required|string',
                'destinationId'                  => 'required|string',
                'departureTime'                  => 'required|string',
                'travelDate'                     => 'required|date',
                'customerId'                     => 'nullable|integer|exists:users,id',
                'serviceType'                    => 'nullable|string',
                'discount'                       => 'nullable|numeric|min:0',
                'discountPerSeat'                => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();

            $customerId = Auth::id() ?: $validated['customerId'] ?? null;

            if (!$customerId) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to continue booking',
                ], 401);
            }

            $basePNR = $this->generateOrGetGroupBasePNR(
                $validated['scheduleId'],
                $validated['sourceId'],
                $validated['destinationId'],
                $validated['travelDate'],
                $validated['departureTime'],
                $validated['companyName']
            );

            if (!$basePNR) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate PNR. Please try again.',
                ], 500);
            }

            $existingBookings = TicketingSeat::where('Customer_Id', $customerId)
                ->where('Ticketing_Schedule_ID', $validated['scheduleId'])
                ->where('Source_ID', $validated['sourceId'])
                ->where('Destination_ID', $validated['destinationId'])
                ->whereDate('Travel_Date', $validated['travelDate'])
                ->where('Travel_Time', $validated['departureTime'])
                ->whereIn('Status', ['Pending', 'Confirm'])
                ->where('Issue_Date', '>=', now()->subMinutes(30))
                ->get();

            // ✅ Convert global city IDs to company-specific city IDs
            $operatorIdInt = (int) $validated['operator_id'];
            $globalFromId  = (int) $validated['sourceId'];
            $globalToId    = (int) $validated['destinationId'];

            $companyFromId = $this->resolveCompanyCityId($operatorIdInt, $globalFromId);
            $companyToId   = $this->resolveCompanyCityId($operatorIdInt, $globalToId);

            if ($companyFromId === null) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No city mapping found for source city ID {$globalFromId} with operator {$operatorIdInt}"
                ], 400);
            }

            if ($companyToId === null) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No city mapping found for destination city ID {$globalToId} with operator {$operatorIdInt}"
                ], 400);
            }

            $createdBookings   = [];
            $totalFare         = 0;
            $totalDiscount     = 0;
            $totalOriginalFare = 0;
            $allSeats          = [];
            $isUpdate          = false;
            $newSeatsAdded     = false;

            foreach ($validated['selectedSeats'] as $seat) {

                // ── Find if this exact seat was already booked by this customer ──
                $existingSeatBooking = $existingBookings->firstWhere('Seat_Id', (string) $seat['seatId']);

                if ($existingSeatBooking) {
                    // ── UPDATE existing booking row ────────────────────────────
                    $existingSeatBooking->update([
                        'Seat_No'              => $seat['label'],
                        'Fare'                 => $seat['fare'],
                        'Original_Fare'        => $seat['original_fare'],
                        'Discount'             => $seat['discount'],
                        'Passenger_Name'       => $validated['fullName'],
                        'Contact_No'           => $validated['phone'],
                        'Emergency_Contact'    => $validated['emergencyContact'],
                        'CNIC'                 => $validated['cnic'],
                        'Gender'               => ucfirst($seat['gender']),
                        'Boarding_Terminal'    => $validated['pickupTerminal'],
                        'Boarding_Terminal_Id' => $validated['pickupTerminalId'],
                        'Issue_Date'           => now(),
                        'Company_Id'           => $validated['operator_id'],
                        'company_source_id'    => $companyFromId,
                        'company_destination_id' => $companyToId,
                    ]);

                    $createdBookings[]  = $existingSeatBooking;
                    $allSeats[]         = $seat['label'];
                    $totalFare         += $seat['fare'];
                    $totalDiscount     += $seat['discount'];
                    $totalOriginalFare += $seat['original_fare'];
                    $isUpdate           = true;

                } else {
                    // ── Guard: prevent double-booking by another user ──────────
                    $seatAlreadyBooked = TicketingSeat::where('Ticketing_Schedule_ID', $validated['scheduleId'])
                        ->where('Source_ID', $validated['sourceId'])
                        ->where('Destination_ID', $validated['destinationId'])
                        ->whereDate('Travel_Date', $validated['travelDate'])
                        ->where('Travel_Time', $validated['departureTime'])
                        ->where('Seat_Id', (string) $seat['seatId'])
                        ->whereIn('Status', ['Pending', 'Confirm'])
                        ->exists();

                    if ($seatAlreadyBooked) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Seat {$seat['label']} is already booked. Please select a different seat.",
                        ], 400);
                    }

                    // ── CREATE new booking row ─────────────────────────────────
                    $pnrWithSeat = $basePNR . '-' . $seat['label'];

                    $booking = TicketingSeat::create([
                        'Ticketing_Schedule_ID' => $validated['scheduleId'],
                        'Seat_Id'               => (string) $seat['seatId'],
                        'Seat_No'               => $seat['label'],
                        'Fare'                  => $seat['fare'],
                        'Original_Fare'         => $seat['original_fare'],
                        'Discount'              => $seat['discount'],
                        'Status'                => 'Pending',
                        'Issue_Date'            => now(),
                        'Travel_Date'           => $validated['travelDate'],
                        'Travel_Time'           => $validated['departureTime'],
                        'Company_Name'          => $validated['companyName'],
                        'Company_Id'            => $validated['operator_id'],
                        'Boarding_Terminal'     => $validated['pickupTerminal'],
                        'Boarding_Terminal_Id'  => $validated['pickupTerminalId'],
                        'Source_ID'             => $companyFromId,
                        'Destination_ID'        => $companyToId,
                        'Passenger_Name'        => $validated['fullName'],
                        'Contact_No'            => $validated['phone'],
                        'Emergency_Contact'     => $validated['emergencyContact'],
                        'CNIC'                  => $validated['cnic'],
                        'Gender'                => ucfirst($seat['gender']),
                        'PNR_No'                => $pnrWithSeat,
                        'CollectionPoint'       => 'Payment Pending',
                        'Customer_Id'           => $customerId,
                        'Points'                => 0,
                        'Bus_Service'           => $validated['serviceType'],
                        'Is_Return'             => false,
                    ]);

                    $createdBookings[]  = $booking;
                    $allSeats[]         = $seat['label'];
                    $totalFare         += $seat['fare'];
                    $totalDiscount     += $seat['discount'];
                    $totalOriginalFare += $seat['original_fare'];
                    $newSeatsAdded      = true;
                }
            }

            // ── Remove any seats the customer deselected since last call ──────
            if ($existingBookings->isNotEmpty()) {
                $selectedSeatIds = collect($validated['selectedSeats'])
                    ->pluck('seatId')
                    ->map(fn($id) => (string) $id)
                    ->toArray();

                $existingBookings->each(function ($booking) use ($selectedSeatIds) {
                    if (!in_array($booking->Seat_Id, $selectedSeatIds)) {
                        $booking->delete();
                    }
                });
            }

            DB::commit();

            $bookingsCollection = collect($createdBookings);

            return response()->json([
                'success' => true,
                'message' => $isUpdate
                    ? ($newSeatsAdded ? 'Booking updated with new seats' : 'Booking updated successfully')
                    : 'Booking created successfully',
                'data' => [
                    'bookings' => $bookingsCollection->map(fn($b) => [
                        'id'            => $b->id,
                        'pnr'           => $b->PNR_No,
                        'base_pnr'      => explode('-', $b->PNR_No)[0],
                        'seat_id'       => $b->Seat_Id,
                        'seat_no'       => $b->Seat_No,
                        'fare'          => $b->Fare,
                        'original_fare' => $b->Original_Fare,
                        'discount'      => $b->Discount,
                        'gender'        => $b->Gender,
                    ])->values()->all(),

                    'passenger_name'      => $validated['fullName'],
                    'email'               => $validated['email'],
                    'phone'               => $validated['phone'],
                    'pnr'                 => $basePNR,
                    'full_pnrs'           => $bookingsCollection->pluck('PNR_No')->toArray(),
                    'seats'               => $allSeats,
                    'total_fare'          => $totalFare,
                    'total_original_fare' => $totalOriginalFare,
                    'total_discount'      => $totalDiscount,
                    'discount_per_seat'   => count($createdBookings) > 0
                        ? round($totalDiscount / count($createdBookings), 2)
                        : 0,
                    'status'              => 'Pending',
                    'travel_date'         => $validated['travelDate'],
                    'departure_time'      => $validated['departureTime'],
                    'company'             => $validated['companyName'],
                    'operator_id'         => $validated['operator_id'],
                    'total_seats'         => count($createdBookings),
                    'is_update'           => $isUpdate,
                    'new_seats_added'     => $newSeatsAdded,
                    'is_group_booking'    => true,
                    'company_from_id'     => $companyFromId,
                    'company_to_id'       => $companyToId,
                ],
            ], ($isUpdate && !$newSeatsAdded) ? 200 : 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('createBooking error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
    // confirmBooking — ✅ uses operator_id from DB booking to find company config
    // =========================================================================
    public function confirmBooking(Request $request)
    {
        try {
            $validated = $request->validate([
                'pnr'         => 'required|string',
                'totalPrice'  => 'required|numeric',
                'companyName' => 'required|string',
                'operator_id' => 'nullable|string', // ✅ accept operator_id
            ]);

            $inputPNR    = $validated['pnr'];
            $basePNR     = explode('-', $inputPNR)[0];

            // Find all bookings with this base PNR
            $bookings = TicketingSeat::where('PNR_No', 'LIKE', $basePNR . '%')
                ->where('Status', 'Pending')
                ->get();

            if ($bookings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending bookings found with this PNR',
                ], 404);
            }

            // ✅ Get operator_id from request OR from saved Company_Id in booking
            $operatorId = $validated['operator_id']
                ?? $bookings->first()->Company_Id
                ?? null;

            if (!$operatorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot determine company. operator_id missing.',
                ], 400);
            }

            // ✅ Find company config by operator_id — not by name
            $companyConfig = $this->findCompanyByOperatorId((string) $operatorId);

            if (!$companyConfig) {
                return response()->json([
                    'success' => false,
                    'message' => "Company config not found for operator_id: {$operatorId}",
                ], 404);
            }

            Log::info('Confirming booking', [
                'base_pnr'    => $basePNR,
                'operator_id' => $operatorId,
                'company'     => $companyConfig['name'],
                'total_seats' => $bookings->count(),
            ]);

            DB::beginTransaction();

            $externalApiResults = [];
            $hasFailedSeat      = false;

            foreach ($bookings as $booking) {
                $apiResult = $this->callExternalUpdateSeatAPI($booking, $companyConfig);

                $externalApiResults[] = [
                    'pnr'     => $booking->PNR_No,
                    'seat_no' => $booking->Seat_No,
                    'result'  => $apiResult,
                ];

                if (!$apiResult['success']) {
                    $hasFailedSeat = true;
                    Log::warning('External API failed for seat', [
                        'pnr'     => $booking->PNR_No,
                        'seat_no' => $booking->Seat_No,
                        'status'  => $apiResult['status'] ?? 'FAILED',
                    ]);
                }
            }

            if ($hasFailedSeat) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'One or more seats could not be confirmed via external API.',
                    'data'    => ['external_api_results' => $externalApiResults],
                ], 500);
            }

            $updateCount = TicketingSeat::where('PNR_No', 'LIKE', $basePNR . '%')
                ->where('Status', 'Pending')
                ->update([
                    'Status'          => 'Confirmed',
                    'PaymentDate'     => now(),
                    'updated_at'      => now(),
                    'Invoice'         => $basePNR,
                    'CollectionPoint' => 'Payment Completed',
                ]);

            if ($updateCount === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update booking status',
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully',
                'data'    => [
                    'base_pnr'             => $basePNR,
                    'pnrs'                 => $bookings->pluck('PNR_No')->toArray(),
                    'booking_status'       => 'Confirmed',
                    'seat_numbers'         => $bookings->pluck('Seat_No')->toArray(),
                    'total_seats'          => $bookings->count(),
                    'company'              => $companyConfig['name'],
                    'operator_id'          => $operatorId,
                    'passenger_name'       => $bookings->first()->Passenger_Name,
                    'total_fare'           => $bookings->sum('Fare'),
                    'payment_date'         => now()->toDateTimeString(),
                    'external_api_results' => $externalApiResults,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('confirmBooking error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // callExternalUpdateSeatAPI
    // =========================================================================
    private function callExternalUpdateSeatAPI($booking, array $companyConfig): array
    {
        try {
            $params = [
                'sid'              => $booking->Seat_Id,
                'pname'            => $booking->Passenger_Name,
                'cno'              => $booking->Contact_No,
                'CNIC'             => $booking->CNIC,
                'g'                => $booking->Gender,
                'from'             => $booking->Source_ID,
                'to'               => $booking->Destination_ID,
                'username'         => $companyConfig['username'],  // ✅ server-side only
                'password'         => $companyConfig['password'],  // ✅ server-side only
                'fare'             => $booking->Fare,
                'invid'            => $booking->PNR_No,
                'd'                => $booking->Discount ?? '0',
                'SelectedTerminal' => $booking->Boarding_Terminal_Id,
            ];

            $response = Http::timeout(15)->get($companyConfig['update_seat'], $params);
            $data     = $response->json();

            $status = null;
            if (is_array($data) && isset($data[0]['status'])) {
                $status = strtoupper($data[0]['status']);
            } elseif (isset($data['status'])) {
                $status = strtoupper($data['status']);
            }

            $success = in_array($status, ['BOOKED', 'SUCCESS']);

            return [
                'success' => $success,
                'seat_id' => $booking->Seat_Id,
                'seat_no' => $booking->Seat_No,
                'pnr'     => $booking->PNR_No,
                'fare'    => $booking->Fare,
                'status'  => $status,
                'response'=> $data,
            ];

        } catch (\Exception $e) {
            Log::error('External API call failed', [
                'pnr'     => $booking->PNR_No,
                'seat_id' => $booking->Seat_Id,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'seat_id' => $booking->Seat_Id,
                'seat_no' => $booking->Seat_No,
                'pnr'     => $booking->PNR_No,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // PNR helpers — unchanged
    // =========================================================================
    private function generateOrGetGroupBasePNR($scheduleId, $sourceId, $destinationId, $travelDate, $travelTime, $companyName): ?string
    {
        $existingBooking = TicketingSeat::where('Ticketing_Schedule_ID', $scheduleId)
            ->where('Source_ID', $sourceId)
            ->where('Destination_ID', $destinationId)
            ->whereDate('Travel_Date', $travelDate)
            ->where('Travel_Time', $travelTime)
            ->whereIn('Status', ['Pending', 'Confirm'])
            ->first();

        if ($existingBooking) {
            $existingPNR = $existingBooking->PNR_No;
            return str_contains($existingPNR, '-')
                ? explode('-', $existingPNR)[0]
                : $existingPNR;
        }

        return $this->generateUniqueBasePNR($companyName);
    }

    private function generateUniqueBasePNR(string $companyName): string
    {
        $cleanName = preg_replace('/[^A-Za-z]/', '', $companyName);
        $prefix    = strtoupper(str_pad(substr($cleanName, 0, 2), 2, 'X'));

        for ($i = 0; $i < 10; $i++) {
            $basePNR = $prefix . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            if (!TicketingSeat::where('PNR_No', 'LIKE', $basePNR . '-%')->exists()) {
                return $basePNR;
            }
        }

        return $prefix . str_pad(time() % 100000000, 8, '0', STR_PAD_LEFT);
    }
}
