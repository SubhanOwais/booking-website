<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerRequest extends Model
{
    protected $fillable = [
        'company_name',
        'contact_person',
        'email',
        'phone',
        'city',
        'company_type',
        'status',
        'company_detail',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
        'company_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'company_id', 'id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePending($query)   { return $query->where('status', 'pending'); }
    public function scopeReviewing($query) { return $query->where('status', 'reviewing'); }
    public function scopeAccepted($query)  { return $query->where('status', 'accepted'); }
    public function scopeRejected($query)  { return $query->where('status', 'rejected'); }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isPending():   bool { return $this->status === 'pending'; }
    public function isReviewing(): bool { return $this->status === 'reviewing'; }
    public function isAccepted():  bool { return $this->status === 'accepted'; }
    public function isRejected():  bool { return $this->status === 'rejected'; }
    public function hasCompany():  bool { return !is_null($this->company_id); }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'yellow',
            'reviewing' => 'blue',
            'accepted'  => 'green',
            'rejected'  => 'red',
            default     => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'Pending',
            'reviewing' => 'Under Review',
            'accepted'  => 'Accepted',
            'rejected'  => 'Rejected',
            default     => 'Unknown',
        };
    }
}
