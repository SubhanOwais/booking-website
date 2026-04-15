<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discount';

    protected $fillable = [
        'name',
        'discount_percentage',
        'main_city_id',
        'mapped_city_ids',
        'company_ids',        // changed from company_names
        'is_active',
        'start_date',
        'end_date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'mapped_city_ids' => 'array',
        'company_ids'     => 'array',    // cast to array
        'is_active'       => 'boolean',
        'start_date'      => 'datetime',
        'end_date'        => 'datetime'
    ];

    // Auto-disable expired discounts on boot
    protected static function booted()
    {
        static::retrieved(function ($discount) {
            if ($discount->is_active && $discount->end_date && now()->gt($discount->end_date)) {
                $discount->is_active = false;
                $discount->saveQuietly();
            }
        });
    }

    // Accessor to get company names from IDs (useful for display)
    public function getCompanyNamesAttribute()
    {
        if (empty($this->company_ids)) {
            return collect(); // empty collection means all companies
        }

        return Company::whereIn('id', $this->company_ids)
            ->pluck('company_name');
    }

    // Helper for array of IDs (already casted, but kept for consistency)
    public function getCompanyIdsArrayAttribute()
    {
        return $this->company_ids ?: [];
    }

    // Existing helper methods
    public function getMappedCityIdsArrayAttribute()
    {
        return $this->mapped_city_ids ?: [];
    }

    public function getStatusAttribute()
    {
        $now = now();

        if ($this->is_active && $this->end_date && $now->gt($this->end_date)) {
            $this->is_active = false;
            $this->saveQuietly();
        }

        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->start_date && $this->end_date) {
            if ($now->lt($this->start_date)) {
                return 'upcoming';
            } elseif ($now->gt($this->end_date)) {
                return 'expired';
            } else {
                return 'active';
            }
        }

        return 'active';
    }

    public function getIsValidAttribute()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        $valid = true;

        if ($this->start_date && $now->lt($this->start_date)) {
            $valid = false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            $valid = false;
        }

        return $valid;
    }

    // Relationships
    public function mainCity()
    {
        return $this->belongsTo(City::class, 'main_city_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeForCity($query, $cityId)
    {
        return $query->active()
            ->where(function ($q) use ($cityId) {
                $q->where('main_city_id', $cityId)
                ->orWhereJsonContains('mapped_city_ids', (int) $cityId)
                ->orWhereJsonContains('mapped_city_ids', (string) $cityId);
            });
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_ids')
            ->orWhereJsonContains('company_ids', (int) $companyId);
        });
    }
}
