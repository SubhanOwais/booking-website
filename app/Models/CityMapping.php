<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityMapping extends Model
{
    protected $table = 'city_mappings';

    // ✅ Use exact database column names (PascalCase)
    protected $fillable = [
        'City_Id',
        'City_Mapping',
        'Active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'City_Id' => 'integer',
        'City_Mapping' => 'array',    // automatically decodes JSON
        'Active' => 'boolean',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // Relationship to the main city (using City_Id)
    public function city()
    {
        return $this->belongsTo(City::class, 'City_Id', 'id');
    }

    public function mappedCities()
    {
        return City::whereIn('id', $this->City_Mapping)->get();
    }

    public function getMappedCityNamesAttribute()
    {
        return City::whereIn('id', $this->City_Mapping)->pluck('City_Name');
    }
}
