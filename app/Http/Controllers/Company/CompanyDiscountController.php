<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\City;
use App\Models\CityMapping;
use App\Models\Company;                 // added
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CompanyDiscountController extends Controller
{
    public function index()
    {
        $user          = Auth::user();
        $isCompanyUser = in_array($user->User_Type, ['CompanyOwner', 'CompanyUser']);
        $companyId     = $isCompanyUser ? $user->Company_Id : null;

        // ── Companies dropdown ────────────────────────────────────────────────
        $companies = Company::select('id', 'company_name as name')
                ->where('id', $companyId)
                ->get();

        // ── Discounts query ───────────────────────────────────────────────────
        $discountQuery = Discount::with(['creator', 'updater', 'mainCity'])->withTrashed()->latest();

        if ($isCompanyUser) {
            $discountQuery->whereJsonContains('company_ids', $companyId);
        }

        $discounts = $discountQuery->get()->map(function ($discount) use ($companies) {
            $mappedCities = [];
            if ($discount->mapped_city_ids) {
                $mappedCities = City::whereIn('id', $discount->mapped_city_ids)
                    ->pluck('City_Name')
                    ->toArray();
            }

            $companyNames   = [];
            $companyDisplay = 'All Companies';
            if (!empty($discount->company_ids)) {
                $companyNames   = $companies->whereIn('id', $discount->company_ids)
                    ->pluck('name')
                    ->toArray();
                $companyDisplay = implode(', ', $companyNames);
            }

            return [
                'id'                   => $discount->id,
                'name'                 => $discount->name,
                'discount_percentage'  => $discount->discount_percentage,
                'main_city_id'         => $discount->main_city_id,
                'main_city_name'       => $discount->mainCity->City_Name ?? 'N/A',
                'mapped_city_ids'      => $discount->mapped_city_ids ?? [],
                'mapped_city_names'    => implode(', ', $mappedCities),
                'mapped_city_names_array' => $mappedCities,
                'company_ids'          => $discount->company_ids ?? [],
                'company_names_array'  => $companyNames,
                'company_names_display'=> $companyDisplay,
                'is_active'            => $discount->is_active,
                'status'               => $discount->status,
                'is_valid'             => $discount->is_valid,
                'start_date'           => $discount->start_date?->format('Y-m-d H:i'),
                'end_date'             => $discount->end_date?->format('Y-m-d H:i'),
                'created_by'           => $discount->creator?->name ?? 'N/A',
                'updated_by'           => $discount->updater?->name ?? 'N/A',
                'created_at'           => $discount->created_at->format('Y-m-d H:i'),
                'deleted_at'           => $discount->deleted_at?->format('Y-m-d H:i'),
            ];
        });

        // ✅ Fix: Get cities that have at least one mapping (using CityMapping table directly)
        $mappedCityIds = CityMapping::pluck('City_Id')->unique();
        $mainCities = City::whereIn('id', $mappedCityIds)
            ->select('id', 'City_Name as name')
            ->orderBy('City_Name')
            ->get();

        return Inertia::render('Company/Discount/Index', [
            'discounts'          => $discounts,
            'mainCities'         => $mainCities,
            'availableCompanies' => $companies,
        ]);
    }

    public function getMappedCities($mainCityId)
    {
        // Use exact database column names from city_mappings table
        $cityMapping = CityMapping::where('City_Id', $mainCityId)
            ->where('Active', true)
            ->first();

        if (!$cityMapping) {
            return response()->json(['success' => false, 'mapped_cities' => []]);
        }

        // City_Mapping is already an array due to the cast in the CityMapping model
        $mappingIds = $cityMapping->City_Mapping;

        if (empty($mappingIds) || !is_array($mappingIds)) {
            return response()->json(['success' => false, 'mapped_cities' => []]);
        }

        // ✅ Use 'id' column (primary key of cities table), not 'City_Id'
        $mappedCities = City::whereIn('id', $mappingIds)
            ->select('id', 'City_Name as name')
            ->orderBy('City_Name')
            ->get();

        return response()->json(['success' => true, 'mapped_cities' => $mappedCities]);
    }

    public function store(Request $request)
    {
        $user          = Auth::user();
        $isCompanyUser = in_array($user->User_Type, ['CompanyOwner', 'CompanyUser']);

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'main_city_id'        => 'required|',        // ✅ use 'id'
            'mapped_city_ids'     => 'required|array|min:1',
            'mapped_city_ids.*'   => 'exists:cities,id',                 // ✅ use 'id'
            'company_ids'         => 'nullable|array',
            'company_ids.*'       => 'required',
            'is_active'           => 'required|boolean',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
        ]);

        $userId = Auth::id();
        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        if ($isCompanyUser) {
            $validated['company_ids'] = [$user->Company_Id];
        } elseif (empty($validated['company_ids'])) {
            $validated['company_ids'] = null;
        }

        Discount::create($validated);

        return redirect()->back()->with('success', 'Discount created successfully!');
    }

    public function update(Request $request, $id)
    {
        $discount      = Discount::withTrashed()->findOrFail($id);
        $user          = Auth::user();
        $isCompanyUser = in_array($user->User_Type, ['CompanyOwner', 'CompanyUser']);

        if ($isCompanyUser && !in_array($user->Company_Id, $discount->company_ids ?? [])) {
            abort(403, 'You do not have permission to update this discount.');
        }

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'main_city_id'        => 'required|exists:cities,id',        // ✅ use 'id'
            'mapped_city_ids'     => 'required|array|min:1',
            'mapped_city_ids.*'   => 'exists:cities,id',                 // ✅ use 'id'
            'company_ids'         => 'nullable|array',
            'company_ids.*'       => 'exists:companies,id',
            'is_active'           => 'required|boolean',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['updated_by'] = Auth::id();

        if ($isCompanyUser) {
            $validated['company_ids'] = [$user->Company_Id];
        } elseif (empty($validated['company_ids'])) {
            $validated['company_ids'] = null;
        }

        $discount->update($validated);

        return redirect()->back()->with('success', 'Discount updated successfully!');
    }

    public function toggleActive($id)
    {
        $discount      = Discount::findOrFail($id);
        $user          = Auth::user();
        $isCompanyUser = in_array($user->User_Type, ['CompanyOwner', 'CompanyUser']);

        // ✅ CompanyOwner/User can only toggle their own company's discounts
        if ($isCompanyUser && !in_array($user->Company_Id, $discount->company_ids ?? [])) {
            abort(403, 'You do not have permission to modify this discount.');
        }

        if (!$discount->is_active && $discount->end_date && now()->gt($discount->end_date)) {
            return redirect()->back()->with('error', 'Cannot activate expired discount. Please update the end date first.');
        }

        $discount->is_active  = !$discount->is_active;
        $discount->updated_by = Auth::id();
        $discount->save();

        return redirect()->back()->with('success',
            $discount->is_active ? 'Discount activated!' : 'Discount deactivated!'
        );
    }

    public function destroy($id)
    {
        $discount      = Discount::findOrFail($id);
        $user          = Auth::user();
        $isCompanyUser = in_array($user->User_Type, ['CompanyOwner', 'CompanyUser']);

        // ✅ CompanyOwner/User can only delete their own company's discounts
        if ($isCompanyUser && !in_array($user->Company_Id, $discount->company_ids ?? [])) {
            abort(403, 'You do not have permission to delete this discount.');
        }

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
            return response()->json(['success' => false, 'discount' => null]);
        }

        return response()->json([
            'success'  => true,
            'discount' => [
                'id'                  => $discount->id,
                'name'                => $discount->name,
                'discount_percentage' => $discount->discount_percentage,
                'company_ids'         => $discount->company_ids,
                'start_date'          => $discount->start_date,
                'end_date'            => $discount->end_date,
            ],
        ]);
    }
}
