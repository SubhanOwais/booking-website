<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'City_Name',
        'City_Abbr',
        'Active',
        'created_by',
    ];

    protected $casts = [
        'Active' => 'boolean',
    ];

    // Relationship with creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope for active cities
    public function scopeActive($query)
    {
        return $query->where('Active', true);
    }

    // Scope for inactive cities
    public function scopeInactive($query)
    {
        return $query->where('Active', false);
    }

    // Scope for search
    public function scopeSearch($query, $search)
    {
        return $query->where('City_Name', 'like', "%{$search}%");
    }
}
