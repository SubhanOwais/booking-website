<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PartnerRequestController extends Controller
{
    // GET /admin/partner-requests
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search', '');

        $query = PartnerRequest::with(['reviewer', 'company'])->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(50)->withQueryString();

        $counts = [
            'all'       => PartnerRequest::count(),
            'pending'   => PartnerRequest::pending()->count(),
            'reviewing' => PartnerRequest::reviewing()->count(),
            'accepted'  => PartnerRequest::accepted()->count(),
            'rejected'  => PartnerRequest::rejected()->count(),
        ];

        return Inertia::render('Admin/PartnerRequests/Index', [
            'requests' => $requests,
            'counts'   => $counts,
            'filters'  => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    // PATCH /admin/partner-requests/{id}/accept
    public function accept(Request $request, PartnerRequest $partnerRequest)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $partnerRequest->update([
            'status'      => 'accepted',
            'admin_notes' => $request->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        return back()->with('success', "✅ {$partnerRequest->company_name} has been accepted.");
    }

    // PATCH /admin/partner-requests/{id}/reject
    public function reject(Request $request, PartnerRequest $partnerRequest)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $partnerRequest->update([
            'status'      => 'rejected',
            'admin_notes' => $request->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        return back()->with('success', "❌ {$partnerRequest->company_name} has been rejected.");
    }

    // PATCH /admin/partner-requests/{id}/reviewing
    public function reviewing(Request $request, PartnerRequest $partnerRequest)
    {
        $partnerRequest->update([
            'status'      => 'reviewing',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        return back()->with('success', "🔍 {$partnerRequest->company_name} marked as under review.");
    }
}
