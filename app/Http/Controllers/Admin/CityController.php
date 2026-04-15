<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    // 🔹 GET ONLY ACTIVE CITIES (used by dropdowns/other modules)
    public function activeCities()
    {
        $cities = City::where('Active', true)
            ->orderBy('City_Name')
            ->get()
            ->map(fn($city) => [
                'id'        => $city->id,
                'City_Name' => $city->City_Name,
            ]);

        return response()->json($cities);
    }

    // 🔹 SHOW ALL CITIES WITH CLIENT-SIDE FILTERING
    public function index(Request $request)
    {
        $cities = City::with('creator')
            ->orderBy('Active', 'desc')
            ->get()
            ->map(fn($city) => [
                'id'         => $city->id,
                'City_Name'  => $city->City_Name,
                'Active'     => $city->Active,
                'created_by' => $city->creator?->name ?? 'System',
            ]);

        return Inertia::render('Admin/Cities/Index', [
            'cities' => $cities,
        ]);
    }

    // 🔹 STORE NEW CITY
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'City_Name' => 'required|string|max:255|unique:cities,City_Name',
            'City_Abbr' => 'nullable|string|max:10|unique:cities,City_Abbr',
            'Active'    => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $city = City::create([
            'City_Name'  => $request->City_Name,
            'City_Abbr' => $request->City_Abbr,
            'Active'     => $request->Active ?? true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.cities')
            ->with('success', 'City created successfully.');
    }

    // 🔹 UPDATE CITY
    public function update(Request $request, City $city)
    {
        $validator = Validator::make($request->all(), [
            'City_Name' => 'required|string|max:255|unique:cities,City_Name,' . $city->id,
            'Active'    => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $city->update([
            'City_Name' => $request->City_Name,
            'City_Abbr' => $request->City_Abbr,
            'Active'    => $request->Active,
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
            'Active' => !$city->Active,
        ]);

        return redirect()->back()
            ->with('success', 'City status updated successfully.');
    }
}
