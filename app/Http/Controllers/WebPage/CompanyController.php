<?php
namespace App\Http\Controllers\WebPage;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    // GET /partner-request — show the form
    public function showPartnerRequest()
    {
        return Inertia::render('Auth/PartnerRequest');
    }

    // POST /partner-request — handle submission
    public function submitPartnerRequest(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'phone'          => 'required|string|max:20',
            'city'           => 'required|string|max:100',
            'company_type'   => 'required|in:bus,hotel,car_rental,tour,other',
            'company_detail' => 'nullable|string|max:3000',
        ]);

        // Prevent duplicate pending requests from same email
        $existing = PartnerRequest::where('email', $validated['email'])
            ->whereIn('status', ['pending', 'reviewing'])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'email' => 'A request from this email is already under review.',
            ])->withInput();
        }

        PartnerRequest::create($validated);

        return back()->with('success', true);
    }
}
