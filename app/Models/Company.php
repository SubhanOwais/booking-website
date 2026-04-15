<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'company_name',
        'company_type',
        'company_logo',
        'company_email',
        'company_phone',
        'helpline_number',
        'city',
        'address',
        'description',
        'is_active',
        'percentage',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url', 'type_label'];

    // ── Relationships ──────────────────────────────────────────────────────

    public function partnerRequest(): BelongsTo
    {
        return $this->belongsTo(PartnerRequest::class, 'company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'Company_Id');
    }

    // Company owner — the user created for this company
    public function owner(): HasOne
    {
        return $this->hasOne(User::class, 'Company_Id')->where('User_Type', 'CompanyOwner');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('company_type', $type);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->company_logo) return null;
        return asset('storage/' . $this->company_logo);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->company_type) {
            'bus'        => 'Bus Company',
            'hotel'      => 'Hotel',
            'car_rental' => 'Car Rental',
            'tour'       => 'Tour Operator',
            'other'      => 'Other',
            default      => ucfirst($this->company_type),
        };
    }
}
