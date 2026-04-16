<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthUserController extends Controller
{
    public function me()
    {
        $user = Auth::guard('web')->user();

        if (!$user) {
            return response()->json(['success' => false, 'user' => null]);
        }

        // Load roles and permissions if using Spatie
        $user->load('roles');

        return response()->json([
            'success' => true,
            'user'    => [
                'id'           => $user->id,
                'Full_Name'    => $user->Full_Name,
                'User_Name'    => $user->User_Name,
                'Email'        => $user->Email,
                'Phone_Number' => $user->Phone_Number,
                'Profile_Picture' => $user->Profile_Picture,
                'profile_picture_url' => $user->Profile_Picture, // from accessor
                'User_Type'    => $user->User_Type,
                'IsSuperAdmin' => (bool) $user->IsSuperAdmin,
                'Is_Active'    => (bool) $user->Is_Active,
                'Company_Id'   => $user->Company_Id,
                'company'      => $user->company ? [
                    'id'   => $user->company->id,
                    'name' => $user->company->name ?? $user->company->Company_Name,
                ] : null,
                // Roles & Permissions
                'roles' => $user->roles->pluck('name'),        // array of role names
                'permissions' => $user->getAllPermissions()->pluck('name'), // all permissions
            ],
        ]);
    }
}
