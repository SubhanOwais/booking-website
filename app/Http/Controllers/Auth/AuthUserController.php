<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthUserController extends Controller
{
    public function me()
    {
        // ✅ Explicitly use web guard (session-based auth)
        $user = Auth::guard('web')->user();

        if (!$user) {
            return response()->json(['success' => false, 'user' => null]);
        }

        return response()->json([
            'success' => true,
            'user'    => [
                'id'           => $user->id,
                'Full_Name'    => $user->Full_Name,
                'User_Name'    => $user->User_Name,
                'Email'        => $user->Email,
                'Phone_Number' => $user->Phone_Number,
                'User_Type'    => $user->User_Type,
                'IsSuperAdmin' => (bool) $user->IsSuperAdmin,
                'Is_Active'    => (bool) $user->Is_Active,
                'Company_Id'   => $user->Company_Id,
                'company'      => $user->company ? [
                    'id'   => $user->company->id,
                    'name' => $user->company->name ?? $user->company->Company_Name,
                ] : null,
                'permissions'  => [
                    'isSuperAdmin'    => (bool) $user->IsSuperAdmin,
                    'isCompanyOwner'  => $user->User_Type === 'CompanyOwner',
                    'isCompanyUser'   => $user->User_Type === 'CompanyUser',
                    'isWebCustomer'   => $user->User_Type === 'WebCustomer',
                    'isCompanyMember' => in_array($user->User_Type, ['CompanyOwner', 'CompanyUser']),
                ],
            ],
        ]);
    }
}
