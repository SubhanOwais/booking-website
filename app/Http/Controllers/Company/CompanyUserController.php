<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class CompanyUserController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index()
    {
        $companyId = Auth::user()->Company_Id;

        $users = User::with('roles')
            ->where('Company_Id', $companyId)
            ->whereIn('User_Type', ['Employee', 'Partner'])
            ->get()
            ->map(function ($user) {
                return [
                    'id'              => $user->id,
                    'name'            => $user->Full_Name,
                    'email'           => $user->Email,
                    'phone_number'    => $user->Phone_Number,
                    'profile_picture' => $user->Profile_Picture,
                    'is_active'       => (bool) $user->Is_Active,
                    'user_type'       => $user->User_Type,
                    'address'         => $user->Address,
                    'roles'           => $user->roles->map(fn($r) => ['name' => $r->name]),
                ];
            });

        return Inertia::render('Company/UserManagement/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Get list of available roles (excluding SuperAdmin, WebCustomer etc.)
     */
    public function getRoles()
    {
        $roles = Role::whereNotIn('name', ['SuperAdmin', 'WebCustomer'])->get(['name']);
        return response()->json($roles);
    }

    /**
     * Store a new company user (Employee or Partner)
     */
    public function store(Request $request)
    {
        $validated = $this->validateUser($request, false);
        $companyId = Auth::user()->Company_Id;

        // Check if email already exists in this company
        if (User::where('Email', $validated['email'])->where('Company_Id', $companyId)->exists()) {
            return response()->json(['errors' => ['email' => ['Email already exists for this company.']]], 422);
        }

        $user = User::create([
            'Full_Name'       => $validated['name'],
            'Email'           => $validated['email'],
            'Phone_Number'    => $validated['phone_number'],
            'Password'        => Hash::make($validated['password']),
            'Is_Active'       => $validated['is_active'],
            'User_Type'       => $validated['userType'],
            'Company_Id'      => $companyId,
            'Address'         => $validated['address'] ?? null,
            'Profile_Picture' => $this->handleProfilePicture($request),
        ]);

        if (!empty($validated['role'])) {
            $user->syncRoles($validated['role']);
        }

        return response()->json(['message' => 'User created successfully'], 200);
    }

    /**
     * Update an existing company user
     */
    public function update(Request $request)
    {
        $validated = $this->validateUser($request, true);
        $user = User::findOrFail($validated['editID']);

        if ($user->Company_Id !== Auth::user()->Company_Id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check email uniqueness (exclude current user)
        if (User::where('Email', $validated['email'])
            ->where('Company_Id', $user->Company_Id)
            ->where('id', '!=', $user->id)
            ->exists()) {
            return response()->json(['errors' => ['email' => ['Email already exists for this company.']]], 422);
        }

        $updateData = [
            'Full_Name'    => $validated['name'],
            'Email'        => $validated['email'],
            'Phone_Number' => $validated['phone_number'],
            'Is_Active'    => $validated['is_active'],
            'User_Type'    => $validated['userType'],
            'Address'      => $validated['address'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['Password'] = Hash::make($validated['password']);
        }

        $newPicture = $this->handleProfilePicture($request, $user->Profile_Picture);
        if ($newPicture) {
            $updateData['Profile_Picture'] = $newPicture;
        }

        $user->update($updateData);

        if (!empty($validated['role'])) {
            $user->syncRoles($validated['role']);
        }

        return response()->json(['message' => 'User updated successfully'], 200);
    }

    /**
     * Delete a company user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->Company_Id !== Auth::user()->Company_Id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function validateUser(Request $request, $isUpdate = false)
    {
        $rules = [
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'is_active'    => 'required|boolean',
            'userType'     => ['required', Rule::in(['Employee', 'Partner'])],
            'role'         => 'required|array|min:1',
            'address'      => 'nullable|string|max:500',
        ];

        if (!$isUpdate) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
            $rules['editID']   = 'required|integer|exists:users,id';
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        $validated['role'] = explode(',', $request->role ?? '');
        $validated['role'] = array_filter($validated['role']);

        return $validated;
    }

    private function handleProfilePicture(Request $request, $oldPicture = null)
    {
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $path = $file->store('profile_pictures', 'public');
            if ($oldPicture && Storage::disk('public')->exists($oldPicture)) {
                Storage::disk('public')->delete($oldPicture);
            }
            return $path;
        }

        if ($request->filled('profile_picture') && !$request->hasFile('profile_picture')) {
            return 'avatars/' . $request->input('profile_picture');
        }

        return $oldPicture;
    }
}
