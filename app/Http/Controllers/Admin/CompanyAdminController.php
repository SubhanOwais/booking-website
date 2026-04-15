<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PartnerRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class CompanyAdminController extends Controller
{
    // GET /admin/companies
    public function index()
    {
        $companies = Company::with(['createdBy', 'owner', 'partnerRequest'])
            ->latest()->get();

        $counts = [
            'total'    => Company::count(),
            'active'   => Company::active()->count(),
            'inactive' => Company::where('is_active', false)->count(),
        ];

        // dd($companies->get());

        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies,
            'counts'    => $counts,
        ]);
    }

    // GET /admin/companies/create/{partnerRequest}
    public function create(PartnerRequest $partnerRequest)
    {
        // Only accepted requests can create a company
        if (!$partnerRequest->isAccepted()) {
            return redirect()->route('admin.partner-requests.index')
                ->withErrors(['error' => 'Only accepted requests can create a company.']);
        }

        // Already has a company
        if ($partnerRequest->company_id) {
            return redirect()->route('admin.companies.index')
                ->with('info', 'This request already has a company created.');
        }

        return Inertia::render('Admin/Companies/Create', [
            'partnerRequest' => $partnerRequest,
        ]);
    }

    // POST /admin/companies
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Company fields
            'partner_request_id' => 'required|exists:partner_requests,id',
            'company_name'       => 'required|string|max:255',
            'company_type'       => 'required|in:bus,hotel,car_rental,tour,other',
            'company_email'      => 'nullable|email|max:255',
            'company_phone'      => 'nullable|string|max:20',
            'helpline_number'    => 'nullable|string|max:20',
            'city'               => 'nullable|string|max:100',
            'address'            => 'nullable|string|max:500',
            'description'        => 'nullable|string|max:2000',
            'is_active'          => 'boolean',
            'company_logo'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'percentage'         => 'required|numeric|min:0|max:100',

            // Owner / User fields
            'owner_full_name'    => 'required|string|max:255',
            'owner_username'     => 'required|string|max:100|unique:users,User_Name',
            'owner_email'        => 'required|email|max:255|unique:users,Email',
            'owner_phone'        => 'required|string|max:20|unique:users,Phone_Number',
            'owner_password'     => ['required', 'confirmed', Password::min(8)],
            'owner_cnic'         => 'required|string|max:20|unique:users,cnic',
        ]);

        // Handle logo upload
        $logoPath = null;
        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')
                ->store('companies/logos', 'public');
        }

        // Create company
        $company = Company::create([
            'company_name'       => $validated['company_name'],
            'company_type'       => $validated['company_type'],
            'company_email'      => $validated['company_email'],
            'company_phone'      => $validated['company_phone'],
            'helpline_number'    => $validated['helpline_number'],
            'city'               => $validated['city'],
            'address'            => $validated['address'],
            'description'        => $validated['description'],
            'is_active'          => $request->boolean('is_active', true),
            'company_logo'       => $logoPath,
            'company_id' => $validated['partner_request_id'],
            'percentage'         => $validated['percentage'] ?? null,
            'created_by'         => Auth::id(),
        ]);

        // Create owner user
        $owner = User::create([
            'Full_Name'    => $validated['owner_full_name'],
            'User_Name'    => $validated['owner_username'],
            'Email'        => $validated['owner_email'],
            'Phone_Number' => $validated['owner_phone'],
            'Password'     => Hash::make($validated['owner_password']),
            'User_Type'    => 'CompanyOwner',
            'Is_Active'    => true,
            'IsSuperAdmin' => false,
            'CNIC'         => $validated['owner_cnic'],
            'Company_Id'   => $company->id,
            'Created_By'   => Auth::id(),
        ]);

        return redirect()->route('admin.companies.index')
            ->with('success', "✅ Company '{$company->company_name}' and owner account created successfully.");
    }

    // PATCH /admin/companies/{company}/toggle
    public function toggle(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);

        $status = $company->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Company {$status} successfully.");
    }
}
