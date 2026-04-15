<?php

namespace App\Http\Controllers\WebPage;


use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\TicketImageService;

class BookingController extends Controller
{
    protected $ticketImageService;

    public function __construct(TicketImageService $ticketImageService)
    {
        $this->ticketImageService = $ticketImageService;
    }

    /**
     * Generate ticket image for confirmed booking
     */
    public function generateTicket(Request $request)
    {
        $request->validate([
            'pnr' => 'required|string',
            'customer_id' => 'nullable|integer'
        ]);

        $pnr = $request->pnr;
        $basePNR = explode('-', $pnr)[0];
        $customerId = $request->customer_id ?? Auth::id();

        // Get bookings
        $bookings = TicketingSeat::where('PNR_No', 'LIKE', $basePNR . '%')
            ->where('Status', 'Confirmed')
            ->where('Customer_Id', $customerId)
            ->get();

        if ($bookings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No confirmed booking found with this PNR',
            ], 404);
        }

        try {
            // Build ticket data
            $ticketData = $this->buildTicketData($bookings, $basePNR);

            // Generate ticket image
            $ticketImage = $this->ticketImageService->generateTicketImage($ticketData);

            return response()->json([
                'success' => true,
                'message' => 'Ticket generated successfully',
                'data' => [
                    'ticket_url' => $ticketImage['url'],
                    'ticket_filename' => $ticketImage['filename'],
                    'pnr' => $basePNR
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ticket: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build ticket data from bookings
     */
    private function buildTicketData($bookings, $basePNR)
    {
        $firstBooking = $bookings->first();
        $operatorId = (int) $firstBooking->Company_Id;

        // ✅ Get company-specific city names via CompanyCity mapping
        $sourceCityName = $this->getCompanyCityName($operatorId, $firstBooking->Source_ID);
        $destCityName   = $this->getCompanyCityName($operatorId, $firstBooking->Destination_ID);

        // Calculate totals
        $totalFare = 0;
        $totalDiscount = 0;

        // Build seats array with each passenger's details
        $seats = $bookings->map(function ($booking) use (&$totalFare, &$totalDiscount) {
            $fare = (float) $booking->Fare;
            $discount = (float) ($booking->Discount_Amount ?? $booking->Discount ?? 0);

            $totalFare += $fare;
            $totalDiscount += $discount;

            return [
                'seat_no' => $booking->Seat_No,
                'pnr_no'  => $booking->PNR_No,
                'fare'    => $fare,
                'discount' => $discount,
                'paid_fare' => $fare - $discount,
                'passenger_name' => $booking->Passenger_Name,
                'contact_no' => $booking->Contact_No,
                'cnic' => $booking->CNIC,
                'gender' => $booking->Gender,
            ];
        })->toArray();

        // ✅ Fetch company name and helpline from DB using operator_id
        $dbCompany = \App\Models\Company::where('company_id', $operatorId)
            ->where('is_active', true)
            ->first(['company_name', 'helpline_number', 'company_logo']);

        // dd($sourceCityName, $destCityName, $dbCompany);

        // Build complete ticket data
        return [
            // Customer (using first booking as primary contact)
            'Customer_Id' => $firstBooking->Customer_Id,
            'customer_name' => $firstBooking->Passenger_Name,
            'contact_no' => $firstBooking->Contact_No,
            'primary_customer_name' => $firstBooking->Passenger_Name,
            'primary_contact_no' => $firstBooking->Contact_No,

            'cnic' => $firstBooking->CNIC,
            'gender' => $firstBooking->Gender,
            'company_name'    => $dbCompany?->company_name ?? $firstBooking->Company_Name,
            'helpline'        => $dbCompany?->helpline_number      ?? 'N/A',
            'operator_id'  => $operatorId,

            // Booking
            'invoice' => $firstBooking->Invoice,
            'pnr_no' => $basePNR,
            'full_pnr_numbers' => $bookings->pluck('PNR_No')->implode(', '),

            // Seats
            'seats' => $seats,
            'seat_numbers' => collect($seats)->pluck('seat_no')->implode(', '),
            'total_seats' => count($seats),
            'total_fare' => $totalFare,
            'total_discount' => $totalDiscount,
            'total_paid_fare' => $totalFare - $totalDiscount,

            // Travel
            'Bus_Service' => $firstBooking->Bus_Service,
            'schedule_id' => $firstBooking->Ticketing_Schedule_ID,
            'source_id' => $firstBooking->Source_ID,
            'destination_id' => $firstBooking->Destination_ID,
            'source_city' => $sourceCityName,
            'dest_city' => $destCityName,
            'travel_date' => $firstBooking->Travel_Date->format('Y-m-d'),
            'travel_time' => $firstBooking->Travel_Time,

            // Dates
            'issue_date' => $firstBooking->Issue_Date->format('Y-m-d H:i:s'),
            'payment_date' => $firstBooking->PaymentDate->format('Y-m-d H:i:s'),
            'Boarding_Terminal' => $firstBooking->Boarding_Terminal,
            'collection_point' => $firstBooking->CollectionPoint,
        ];
    }

    /**
     * Get company-specific city name using CompanyCity mapping
     *
     * @param int $operatorId  Company's operator_id (from companies table)
     * @param int $globalCityId Global city ID (cities.id)
     * @return string
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

        // Fallback to global city name using the original Source_ID / Destination_ID
        return \App\Models\City::where('id', $globalCityId)->value('City_Name') ?? 'Unknown';
    }
}
