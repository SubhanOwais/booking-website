<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WebsiteController extends Controller
{
    public function index()
    {
        $websites = Website::latest()->get();
        return Inertia::render('Admin/Website/Index', [
            'websites' => $websites,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,png,jpeg,webp|max:2048',
            'description' => 'nullable|string',
            'helpline_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'facebook' => 'nullable|url',
            'twitter' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'youtube' => 'nullable|url',
            'tiktok' => 'nullable|url',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['logo']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Website::create($data);

        return redirect()->back()->with('success', 'Website added successfully.');
    }

    public function update(Request $request, Website $website)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,png,jpeg,webp|max:2048',
            'description' => 'nullable|string',
            'helpline_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'facebook' => 'nullable|url',
            'twitter' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'youtube' => 'nullable|url',
            'tiktok' => 'nullable|url',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['logo']);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($website->logo && Storage::disk('public')->exists($website->logo)) {
                Storage::disk('public')->delete($website->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $website->update($data);

        return redirect()->back()->with('success', 'Website updated successfully.');
    }

    public function destroy(Website $website)
    {
        // Delete files if they exist
        if ($website->logo && Storage::disk('public')->exists($website->logo)) {
            Storage::disk('public')->delete($website->logo);
        }

        $website->delete();

        return redirect()->back()->with('success', 'Website deleted successfully.');
    }
}
