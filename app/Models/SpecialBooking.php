<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialBooking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'passenger_name',
        'passenger_phone',
        'passenger_email',
        'from_city_id',
        'to_city_id',
        'travel_date',
        'preferred_time',
        'company_id',
        'bus_type',
        'special_notes',
        'status',
        'quoted_price',
        'change_by',
        'created_by',
    ];

    protected $casts = [
        'travel_date'   => 'date',
        'quoted_price'  => 'decimal:2',
    ];

    protected $appends = ['bus_type_label', 'status_label'];

    // ── Relationships ─────────────────────────────────────────────────────

    public function fromCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'from_city_id', 'City_Id');
    }

    public function toCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'to_city_id', 'City_Id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getBusTypeLabelAttribute(): string
    {
        return match ($this->bus_type) {
            'standard'      => 'Standard Bus',
            'luxury'        => 'Luxury Coach',
            'sleeper'       => 'Sleeper Bus',
            'mini_coach'    => 'Mini Coach',
            'double_decker' => 'Double Decker',
            default         => ucfirst($this->bus_type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Pending Review',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default     => ucfirst($this->status),
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByDate($query, string $date)
    {
        return $query->whereDate('travel_date', $date);
    }
}
