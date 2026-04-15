<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CityMapping;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CityMappingController extends Controller
{
    public function index()
    {
        $mappings = CityMapping::with('city')->latest()->get();
        $cities = City::where('Active', true)->select('id', 'City_Name')->get();

        return Inertia::render('Admin/CityMapping/Index', [
            'mappings' => $mappings,
            'cities' => $cities,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities,id',       // ✅ matches cities.id
            'city_mapping' => 'required|array|min:1',
            'city_mapping.*' => 'required|exists:cities,id',
        ]);

        // Check if mapping already exists for this city
        $exists = CityMapping::where('city_id', $request->city_id)->exists();
        if ($exists) {
            return back()->withErrors(['city_id' => 'Mapping already exists for this city'])->withInput();
        }

        // Ensure all values are integers
        $cityIds = array_map('intval', $request->city_mapping);

        // dd($cityIds, auth()->id());

        CityMapping::create([
            'city_id' => $request->city_id,
            'city_mapping' => $cityIds,
            'active' => $request->active ?? true,
            'created_at' => now(),
        ]);

        return redirect()->route('admin.city-mapping.index')
            ->with('success', 'City mapping created successfully');
    }

    public function edit($id)
    {
        $mapping = CityMapping::with('city')->findOrFail($id);
        $cities = City::orderBy('City_Name')->get(['id', 'City_Name']); // ✅

        return response()->json([
            'mapping' => $mapping,
            'cities' => $cities,
        ]);
    }

    public function update(Request $request, $id)
    {
        $mapping = CityMapping::findOrFail($id);

        $request->validate([
            'city_id' => 'required|exists:cities,id',
            'city_mapping' => 'required|array|min:1',
            'city_mapping.*' => 'required|exists:cities,id',
        ]);

        $exists = CityMapping::where('city_id', $request->city_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['city_id' => 'Mapping already exists for this city'])->withInput();
        }

        $cityIds = array_map('intval', $request->city_mapping);

        $mapping->update([
            'city_id' => $request->city_id,
            'city_mapping' => $cityIds,
            'active' => $request->active ?? true,
            'updated_at' => now(),
        ]);

        // ✅ Use to_route() instead of redirect()
        return to_route('admin.city-mapping.index')
            ->with('success', 'City mapping updated successfully');
    }

    public function destroy($id)
    {
        $mapping = CityMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->route('admin.city-mapping.index')
            ->with('success', 'City mapping deleted successfully');
    }
}
