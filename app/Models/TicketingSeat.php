<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketingSeat extends Model
{
    use HasFactory;

    protected $table = 'ticketing_seats';

    protected $fillable = [
        'Ticketing_Schedule_ID',
        'Seat_Id',
        'Seat_No',
        'Status',
        'Travel_Date',
        'Travel_Time',
        'Issue_Date',
        'Boarding_Terminal_Id',
        'Boarding_Terminal',
        'Issued_By',
        'Source_ID',
        'Destination_ID',
        'Passenger_Name',
        'Contact_No',
        'Emergency_Contact',
        'Fare',
        'Original_Fare',
        'Discount',
        'CNIC',
        'Gender',
        'Bus_Service',
        'PNR_No',
        'PaymentDate',
        'Refund_Date',
        'Refund_Reason',
        'Refund_Amount',
        'Refund_By',
        'Is_SMS_Sent',
        'Telenor',
        'IsMissed',
        'CollectionPoint',
        'Customer_Id',
        'Points',
        'Invoice',
        'Is_Return',
        'Company_Name',
        'Company_Id',
    ];

    protected $casts = [
        'Issue_Date' => 'datetime',
        'PaymentDate' => 'datetime',
        'Refund_Date' => 'datetime',
        'Travel_Date' => 'date',
        'Is_SMS_Sent' => 'boolean',
        'Telenor' => 'boolean',
        'IsMissed' => 'boolean',
        'Is_Return' => 'boolean',
        'Fare' => 'decimal:2',
        'Original_Fare' => 'decimal:2',
        'Discount' => 'decimal:2',
        'Refund_Amount' => 'decimal:2',
        'Points' => 'integer',
    ];

    /**
     * Generate unique PNR number
     */
    public static function generatePNR($prefix = 'PNR')
    {
        do {
            $pnr = $prefix . strtoupper(substr(uniqid(), -8));
        } while (self::where('PNR_No', $pnr)->exists());

        return $pnr;
    }

    /**
     * Set emergency contact attribute
     */
    public function setEmergencyContactAttribute($value)
    {
        $this->attributes['Emergency_Contact'] = trim($value);
    }

    /**
     * Relationship: From City
     */
    public function fromCity()
    {
        return $this->belongsTo(City::class, 'Source_ID', 'id');
    }

    /**
     * Relationship: To City
     */
    public function toCity()
    {
        return $this->belongsTo(City::class, 'Destination_ID', 'id');
    }

    /**
     * Relationship: Customer/User
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'Customer_Id');
    }

    /**
     * Relationship: User (alternative)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'Customer_Id');
    }

    /**
     * Scope: Get all bookings for a specific customer and schedule
     */
    public function scopeForCustomerSchedule($query, $customerId, $scheduleId)
    {
        return $query->where('Customer_Id', $customerId)
                     ->where('Ticketing_Schedule_ID', $scheduleId);
    }

    /**
     * Scope: Get pending bookings
     */
    public function scopePending($query)
    {
        return $query->where('Status', 'Pending');
    }

    /**
     * Scope: Get booked tickets
     */
    public function scopeBooked($query)
    {
        return $query->where('Status', 'Booked');
    }

    /**
     * Scope: Recent bookings (within timeframe)
     */
    public function scopeRecent($query, $minutes = 30)
    {
        return $query->where('Issue_Date', '>=', now()->subMinutes($minutes));
    }

    /**
     * Check if booking is expired (Pending for more than 30 minutes)
     */
    public function isPendingExpired()
    {
        if ($this->Status !== 'Pending') {
            return false;
        }

        return $this->Issue_Date && $this->Issue_Date->lt(now()->subMinutes(30));
    }

    /**
     * Get formatted fare
     */
    public function getFormattedFareAttribute()
    {
        return number_format($this->Fare, 0) . ' PKR';
    }

    /**
     * Get formatted travel date
     */
    public function getFormattedTravelDateAttribute()
    {
        return $this->Travel_Date ? $this->Travel_Date->format('d M, Y') : null;
    }

    /**
     * Group bookings by PNR prefix (for grouped display)
     * Returns collection of bookings that belong to same booking session
     */
    public static function getGroupedBookings($customerId, $scheduleId, $status = null)
    {
        $query = self::where('Customer_Id', $customerId)
                     ->where('Ticketing_Schedule_ID', $scheduleId);

        if ($status) {
            $query->where('Status', $status);
        }

        return $query->orderBy('Issue_Date', 'desc')->get();
    }

    /**
     * Calculate total fare for multiple seats
     */
    public static function calculateTotalFare($bookings)
    {
        return $bookings->sum('Fare');
    }

    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'Refund_By', 'id');
    }
}
