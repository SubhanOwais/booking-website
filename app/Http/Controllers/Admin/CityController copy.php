<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    // 🔹 GET ONLY ACTIVE CITIES
    public function activeCities()
    {
        $cities = City::where('Active', true)
            ->orderBy('City_Name')
            ->get()
            ->map(function ($city) {
                return [
                    'Id' => $city->City_Id,
                    'CityName' => $city->City_Name,
                ];
            });

        return response()->json($cities);
    }

    // 🔹 SHOW ALL CITIES WITH REAL-TIME FILTERING
    public function index(Request $request)
    {
        $query = City::query();

        // Search by city name
        if ($request->has('search_name') && !empty($request->search_name)) {
            $search = $request->search_name;
            $query->where('City_Name', 'like', "%{$search}%");
        }

        // Search by city ID
        if ($request->has('search_id') && !empty($request->search_id)) {
            $cityId = $request->search_id;
            $query->where('City_Id', $cityId);
        }

        // Status filter
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('Active', $request->status === 'active');
        }

        // Get all cities as an array
        $cities = $query->orderBy('City_Name')->get()->toArray();

        return Inertia::render('Admin/Cities/Index', [
            'cities' => $cities,
            'filters' => $request->only(['search_name', 'search_id', 'status'])
        ]);
    }

    // 🔹 FILTER CITIES - SEPARATE FUNCTION (can be removed if using index only)
    public function filter(Request $request)
    {
        // Redirect to index with filter parameters
        return redirect()->route('admin.cities', $request->all());
    }


    // 🔹 STORE NEW CITY
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'City_Name' => 'required|string|max:255|unique:cities,City_Name',
            'City_Abbr' => 'nullable|string|max:10',
            'Active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        City::create([
            'City_Name' => $request->City_Name,
            'City_Abbr' => $request->City_Abbr,
            'Active' => $request->Active ?? true,
            'created_by' => auth()->id()
        ]);

        return redirect()->route('admin.cities')
            ->with('success', 'City created successfully.');
    }

    // 🔹 UPDATE CITY
    public function update(Request $request, City $city)
    {
        $validator = Validator::make($request->all(), [
            'City_Name' => 'required|string|max:255|unique:cities,City_Name,' . $city->City_Id . ',City_Id',
            'City_Abbr' => 'nullable|string|max:10',
            'Active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $city->update([
            'City_Name' => $request->City_Name,
            'City_Abbr' => $request->City_Abbr,
            'Active' => $request->Active
        ]);

        return redirect()->back()
            ->with('success', 'City updated successfully.');
    }

    // 🔹 DELETE CITY
    public function destroy(City $city)
    {
        $city->delete();

        return redirect()->route('admin.cities')
            ->with('success', 'City deleted successfully.');
    }

    // 🔹 TOGGLE STATUS (Active/Inactive)
    public function toggleStatus(City $city)
    {
        $city->update([
            'Active' => !$city->Active
        ]);

        return redirect()->back()
            ->with('success', 'City status updated successfully.');
    }

    // 🔹 IMPORT CITIES (RUN ONLY ONCE)
    public function importCities(Request $request)
    {
        try {
            $response = Http::timeout(30)->acceptJson()->get(
                config('services.api.base_url') . '/City',
                [
                    'username' => config('services.api.username'),
                    'password' => config('services.api.password'),
                ]
            );

            if ($response->failed()) {
                return redirect()->back()
                    ->with('error', 'City API error: ' . $response->status());
            }

            $cities = $response->json();
            $imported = 0;
            $skipped = 0;

            foreach ($cities as $cityData) {
                // Check if city already exists
                $existingCity = City::where('City_Id', $cityData['Id'])->first();

                if ($existingCity) {
                    // City already exists, update only if needed
                    if ($existingCity->City_Name !== $cityData['CityName']) {
                        $existingCity->update(['City_Name' => $cityData['CityName']]);
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Create new city
                    City::create([
                        'City_Id' => $cityData['Id'],
                        'City_Name' => $cityData['CityName'],
                        'City_Abbr' => null,
                        'Active' => false,
                        'created_by' => auth()->id()
                    ]);
                    $imported++;
                }
            }

            return redirect()->back()
                ->with('success', "Cities imported: {$imported} new/updated, {$skipped} skipped (already exist).");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
