<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        // dd('Testing');
        return Inertia::render('Company/RoleManagement/Index');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'permissions' => 'required|array',
                'editId' => 'nullable|exists:roles,id'
            ]);

            $companyId = Auth::user()->Company_Id;

            // Check uniqueness within the company
            $existingRole = Role::where('name', $request->name)
                ->where('company_id', $companyId)
                ->when($request->editId, fn($q) => $q->where('id', '!=', $request->editId))
                ->exists();

            if ($existingRole) {
                return response()->json(['status' => 'error', 'message' => 'Role name already exists in your company.'], 422);
            }

            if ($request->editId) {
                $role = Role::findOrFail($request->editId);
                if ($role->company_id != $companyId) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
                }
                $role->update([
                    'name' => $request->name,
                    'created_by' => Auth::user()->Full_Name,
                ]);
            } else {
                $role = Role::create([
                    'name' => $request->name,
                    'guard_name' => 'web',
                    'company_id' => $companyId,
                    'created_by' => Auth::user()->Full_Name,
                ]);
            }

            $role->syncPermissions($request->permissions);

            return response()->json([
                'status' => 'success',
                'message' => $request->editId ? 'Role updated.' : 'Role created.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function get_roles()
    {
        $roles = Role::with('permissions')
            ->where('company_id', Auth::user()->Company_Id)
            ->get();
        return response()->json($roles);
    }

    public function delete_role($id)
    {
        $role = Role::where('company_id', Auth::user()->Company_Id)
            ->where('id', $id)
            ->first();

        if (!$role) {
            return response()->json(['status' => false, 'message' => 'Role not found']);
        }

        $role->delete();
        return response()->json(['status' => true]);
    }
}
