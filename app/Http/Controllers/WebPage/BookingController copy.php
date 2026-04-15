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
            'pnr' => 'required|string'
        ]);

        $pnr = $request->pnr;
        $basePNR = explode('-', $pnr)[0];
        $customerId = Auth::id();

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

        // City names
        $sourceCityName = DB::table('cities')
            ->where('City_ID', $firstBooking->Source_ID)
            ->value('City_Name') ?? 'Unknown';

        $destCityName = DB::table('cities')
            ->where('City_ID', $firstBooking->Destination_ID)
            ->value('City_Name') ?? 'Unknown';

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
                'paid_fare' => $fare - $discount, // Calculate actual paid amount
                'passenger_name' => $booking->Passenger_Name,
                'contact_no' => $booking->Contact_No,
                'cnic' => $booking->CNIC,
                'gender' => $booking->Gender,
            ];
        })->toArray();

        // Build complete ticket data
        return [
            // Customer (using first booking as primary contact)
            'Customer_Id' => $firstBooking->Customer_Id,
            // Keep old key names for compatibility with TicketImageService
            'customer_name' => $firstBooking->Passenger_Name,
            'contact_no' => $firstBooking->Contact_No,
            // Also include new key names
            'primary_customer_name' => $firstBooking->Passenger_Name,
            'primary_contact_no' => $firstBooking->Contact_No,

            'cnic' => $firstBooking->CNIC,
            'gender' => $firstBooking->Gender,
            'company_name' => $firstBooking->Company_Name,

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
}
