<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    // Define your permission structure exactly as in your other project
    public $permissions = [
        'ticketing-reports' => [
            'label' => 'Ticketing Reports',
            'permissions' => [
                'ticketing-reports' => 'ticketing Reports',
            ],
        ],
        'refunds-reports' => [
            'label' => 'Refunds Reports',
            'permissions' => [
                'refunds-reports' => 'refunds Reports',
            ],
        ],
        'refunds-management' => [
            'label' => 'Refunds Management',
            'permissions' => [
                'refunds-management' => 'refunds Management',
                'create-refunds' => 'Create Refunds',
                'edit-refunds' => 'Edit Refunds',
                'delete-refunds' => 'Delete Refunds',
            ],
        ],
        'discount-management' => [
            'label' => 'Discount Management',
            'permissions' => [
                'discount-management' => 'Discount Management',
                'create-discounts' => 'Create Discounts',
                'edit-discounts' => 'Edit Discounts',
                'delete-discounts' => 'Delete Discounts',
            ],
        ],
        'city-management' => [
            'label' => 'City Management',
            'permissions' => [
                'city-management' => 'City Management',
                'create-cities' => 'Create Cities',
                'edit-cities' => 'Edit Cities',
                'delete-cities' => 'Delete Cities',
            ],
        ],
        'users-management' => [
            'label' => 'User Management',
            'permissions' => [
                'users-management' => 'Users Management',
                'create-users' => 'Create Users',
                'edit-users' => 'Edit Users',
                'delete-users' => 'Delete Users',
            ],
        ],
        'roles-management' => [
            'label' => 'Role Management',
            'permissions' => [
                'roles-management' => 'Roles Management',
                'create-roles' => 'Create Roles',
                'edit-roles' => 'Edit Roles',
                'delete-roles' => 'Delete Roles',
            ],
        ],
        'settings-management' => [
            'label' => 'Settings Management',
            'permissions' => [
                'settings-management' => 'Settings Management',
                'edit-settings' => 'Edit Settings',
            ],
        ],
        // ... add all your other sections as needed
    ];

    public function get_permissions()
    {
        return response()->json($this->permissions);
    }
}
