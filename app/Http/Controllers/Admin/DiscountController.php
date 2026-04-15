<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\City;
use App\Models\CityMapping;
use App\Models\Company;                 // added
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DiscountController extends Controller
{
    public function index()
    {
        // Fetch all active companies for dropdown (only id and name)
        $companies = Company::select('id', 'company_name as name')
            ->orderBy('company_name')
            ->get();

        $discounts = Discount::with(['creator', 'updater', 'mainCity'])
            ->withTrashed()
            ->latest()
            ->get()
            ->map(function ($discount) use ($companies) {
                $mappedCities = [];
                if ($discount->mapped_city_ids) {
                    $mappedCities = City::whereIn('id', $discount->mapped_city_ids)
                        ->pluck('City_Name')
                        ->toArray();
                }

                // Get company names from IDs using the preloaded companies collection
                $companyNames = [];
                $companyDisplay = 'All Companies';
                if (!empty($discount->company_ids)) {
                    $companyNames = $companies->whereIn('id', $discount->company_ids)
                        ->pluck('name')
                        ->toArray();
                    $companyDisplay = implode(', ', $companyNames);
                }

                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'discount_percentage' => $discount->discount_percentage,
                    'main_city_id' => $discount->main_city_id,
                    'main_city_name' => $discount->mainCity->City_Name ?? 'N/A',
                    'mapped_city_ids' => $discount->mapped_city_ids ?? [],
                    'mapped_city_names' => implode(', ', $mappedCities),
                    'mapped_city_names_array' => $mappedCities,
                    'company_ids' => $discount->company_ids ?? [],          // changed
                    'company_names_array' => $companyNames,                // for display
                    'company_names_display' => $companyDisplay,            // for search
                    'is_active' => $discount->is_active,
                    'status' => $discount->status,
                    'is_valid' => $discount->is_valid,
                    'start_date' => $discount->start_date?->format('Y-m-d H:i'),
                    'end_date' => $discount->end_date?->format('Y-m-d H:i'),
                    'created_by' => $discount->creator?->name ?? 'N/A',    // fixed relation
                    'updated_by' => $discount->updater?->name ?? 'N/A',    // fixed relation
                    'created_at' => $discount->created_at->format('Y-m-d H:i'),
                    'deleted_at' => $discount->deleted_at?->format('Y-m-d H:i'),
                ];
            });

        $mappedCityIds = CityMapping::pluck('City_Id')->unique();
        $mainCities = City::whereIn('id', $mappedCityIds)
            ->select('id', 'City_Name as name')
            ->orderBy('City_Name')
            ->get();

        return Inertia::render('Admin/Discount/Index', [
            'discounts' => $discounts,
            'mainCities' => $mainCities,
            'availableCompanies' => $companies   // now array of {id, name}
        ]);
    }

    public function getMappedCities($mainCityId)
    {
        $cityMapping = CityMapping::where('City_Id', $mainCityId)
            ->where('Active', true)
            ->first();

        if (!$cityMapping || !$cityMapping->City_Mapping) {
            return response()->json([
                'success' => false,
                'mapped_cities' => []
            ]);
        }

        $mappedCities = City::whereIn('City_Id', $cityMapping->City_Mapping)
            ->select('City_Id as id', 'City_Name as name')
            ->orderBy('City_Name')
            ->get();

        return response()->json([
            'success' => true,
            'mapped_cities' => $mappedCities
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'main_city_id' => 'required|exists:cities,City_Id',
            'mapped_city_ids' => 'required|array|min:1',
            'mapped_city_ids.*' => 'exists:cities,City_Id',
            'company_ids' => 'nullable|array',                     // changed
            'company_ids.*' => 'exists:companies,id',              // validate IDs exist
            'is_active' => 'required|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $userId = Auth::id();
        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        // If company_ids is empty, set to null (applies to all companies)
        if (empty($validated['company_ids'])) {
            $validated['company_ids'] = null;
        }

        Discount::create($validated);

        return redirect()->back()->with('success', 'Discount created successfully!');
    }

    public function update(Request $request, $id)
    {
        $discount = Discount::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'main_city_id' => 'required|exists:cities,City_Id',
            'mapped_city_ids' => 'required|array|min:1',
            'mapped_city_ids.*' => 'exists:cities,City_Id',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
            'is_active' => 'required|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $userId = Auth::id();
        $validated['updated_by'] = $userId;

        // If company_ids is empty, set to null (applies to all companies)
        if (empty($validated['company_ids'])) {
            $validated['company_ids'] = null;
        }

        $discount->update($validated);

        return redirect()->back()->with('success', 'Discount updated successfully!');
    }

    public function toggleActive($id)
    {
        $discount = Discount::findOrFail($id);

        if (!$discount->is_active && $discount->end_date && now()->gt($discount->end_date)) {
            return redirect()->back()->with('error', 'Cannot activate expired discount. Please update the end date first.');
        }

        $discount->is_active = !$discount->is_active;
        $discount->updated_by = Auth::id();
        $discount->save();

        return redirect()->back()->with('success',
            $discount->is_active ? 'Discount activated!' : 'Discount deactivated!'
        );
    }

    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return redirect()->back()->with('success', 'Discount deleted successfully!');
    }

    public function restore($id)
    {
        $discount = Discount::withTrashed()->findOrFail($id);
        $discount->restore();

        return redirect()->back()->with('success', 'Discount restored successfully!');
    }

    public function forceDelete($id)
    {
        $discount = Discount::withTrashed()->findOrFail($id);
        $discount->forceDelete();

        return redirect()->back()->with('success', 'Discount permanently deleted!');
    }

    public function getDiscountForCity($cityId)
    {
        $discount = Discount::forCity($cityId)->first();

        if (!$discount) {
            return response()->json([
                'success' => false,
                'discount' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'discount' => [
                'id' => $discount->id,
                'name' => $discount->name,
                'discount_percentage' => $discount->discount_percentage,
                'company_ids' => $discount->company_ids,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date
            ]
        ]);
    }
}
