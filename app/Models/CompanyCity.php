<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyCity extends Model
{
    use HasFactory;

    protected $table = 'company_cities';

    protected $fillable = [
        'company_id',
        'city_id',
        'key_id',
        'created_by',
        'active',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'city_id'    => 'integer',
        'key_id'     => 'integer',
        'active' => 'boolean',
    ];

    public function city()
    {
        // ✅ key_id references cities.id (global city ID)
        return $this->belongsTo(City::class, 'key_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
