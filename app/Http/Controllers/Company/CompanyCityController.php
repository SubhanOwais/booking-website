<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CompanyCity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class CompanyCityController extends Controller
{
    private function getCompanyId(): int
    {
        return (int) auth()->user()->Company_Id;
    }

    // INDEX
    public function index()
    {
        $companyId = $this->getCompanyId();

        $companyCities = CompanyCity::with(['city', 'creator'])
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($cc) => [
                'id'              => $cc->id,
                'company_city_id' => $cc->city_id,
                'global_city_id'  => $cc->key_id,
                'city_name'       => $cc->city?->City_Name ?? '-',
                'active'          => $cc->active,
                'created_by'      => $cc->creator?->name ?? 'System',
                'created_at'      => $cc->created_at->format('Y-m-d'),
            ]);

        $linkedGlobalIds = CompanyCity::where('company_id', $companyId)->pluck('key_id');

        $availableCities = City::where('Active', true)
            ->whereNotIn('id', $linkedGlobalIds)
            ->orderBy('City_Name')
            ->get()
            ->map(fn($city) => [
                'id'        => $city->id,
                'City_Name' => $city->City_Name,
            ]);

        return Inertia::render('Company/CompanyCities/Index', [
            'companyCities'   => $companyCities,
            'availableCities' => $availableCities,
        ]);
    }

    // STORE
    public function store(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validator = Validator::make($request->all(), [
            'global_city_id'  => 'required|exists:cities,id',
            'company_city_id' => 'required|integer',
            'active'          => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $exists = CompanyCity::where('company_id', $companyId)
            ->where('key_id', $request->global_city_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['global_city_id' => 'This city is already added to your company.'])->withInput();
        }

        $ownIdExists = CompanyCity::where('company_id', $companyId)
            ->where('city_id', $request->company_city_id)
            ->exists();

        if ($ownIdExists) {
            return back()->withErrors(['company_city_id' => 'This company city ID is already in use.'])->withInput();
        }

        CompanyCity::create([
            'company_id' => $companyId,
            'city_id'    => $request->company_city_id,
            'key_id'     => $request->global_city_id,
            'active'     => $request->boolean('active', true),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'City added successfully.');
    }

    // UPDATE
    public function update(Request $request, CompanyCity $companyCity)
    {
        $companyId = $this->getCompanyId();

        // FIX: cast both to int to avoid strict type mismatch causing 403
        if ((int) $companyCity->company_id !== $companyId) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'global_city_id'  => 'required|exists:cities,id',
            'company_city_id' => 'required|integer',
            'active'          => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $exists = CompanyCity::where('company_id', $companyId)
            ->where('key_id', $request->global_city_id)
            ->where('id', '!=', $companyCity->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['global_city_id' => 'This city is already added to your company.'])->withInput();
        }

        $ownIdExists = CompanyCity::where('company_id', $companyId)
            ->where('city_id', $request->company_city_id)
            ->where('id', '!=', $companyCity->id)
            ->exists();

        if ($ownIdExists) {
            return back()->withErrors(['company_city_id' => 'This company city ID is already in use.'])->withInput();
        }

        $companyCity->update([
            'city_id' => $request->company_city_id,
            'key_id'  => $request->global_city_id,
            'active'  => $request->boolean('active'),
        ]);

        return back()->with('success', 'City updated successfully.');
    }

    // DESTROY
    public function destroy(CompanyCity $companyCity)
    {
        $companyId = $this->getCompanyId();

        // FIX: cast both to int
        if ((int) $companyCity->company_id !== $companyId) {
            abort(403);
        }

        $companyCity->delete();

        return back()->with('success', 'City removed successfully.');
    }

    // TOGGLE ACTIVE STATUS
    public function toggleActive(CompanyCity $companyCity)
    {
        $companyId = $this->getCompanyId();

        // FIX: cast both to int
        if ((int) $companyCity->company_id !== $companyId) {
            abort(403);
        }

        $companyCity->update(['active' => !$companyCity->active]);

        return back()->with('success', 'City status updated.');
    }
}
