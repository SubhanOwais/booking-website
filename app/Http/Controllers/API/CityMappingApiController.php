<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CityMapping;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CityMappingApiController extends Controller
{
    /**
     * Get all main cities (cities that have at least one active mapping)
     */
    public function getMainCities()
    {
        try {
            $mainCities = Cache::remember('main_cities', now()->addMinutes(30), function () {
                return CityMapping::where('Active', true)
                    ->with('city:id,City_Name')   // ✅ correct: city.id, city.City_Name
                    ->get()
                    ->pluck('city')
                    ->filter()                     // remove any nulls
                    ->unique('id')                 // ensure uniqueness
                    ->values()
                    ->map(fn($city) => [
                        'Id'       => $city->id,
                        'CityName' => $city->City_Name,
                    ]);
            });

            return response()->json(['success' => true, 'data' => $mainCities]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch main cities',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get mapped cities for a specific main city (the "TO" cities)
     */
    public function getMappedCities(Request $request)
    {
        $request->validate([
            'city_id' => 'required|integer|exists:cities,id',   // ✅ foreign key is city.id
        ]);

        try {
            $cityId = $request->city_id;

            // Fetch the mapping record (active, for this city)
            $mapping = CityMapping::where('Active', true)
                ->where('City_Id', $cityId)
                ->first(['City_Mapping']);   // only get the JSON column

            if (!$mapping || empty($mapping->City_Mapping)) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'message' => 'No mapped cities found'
                ]);
            }

            $mappedCityIds = $mapping->City_Mapping; // already array thanks to $casts

            // Fetch the actual cities
            $mappedCities = City::whereIn('id', $mappedCityIds)
                ->orderBy('City_Name')
                ->get(['id', 'City_Name'])
                ->map(fn($city) => [
                    'Id'       => $city->id,
                    'CityName' => $city->City_Name,
                ]);

            return response()->json(['success' => true, 'data' => $mappedCities]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mapped cities',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
