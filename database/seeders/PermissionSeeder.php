<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Get all permission keys from your PermissionController
        $permissions = [
            // Ticketing Reports
            'ticketing-reports',

            // Refunds Reports
            'refunds-reports',

            // Refunds Management
            'refunds-management',
            'create-refunds',
            'edit-refunds',
            'delete-refunds',

            // Discount Management
            'discount-management',
            'create-discounts',
            'edit-discounts',
            'delete-discounts',

            // City Management
            'city-management',
            'create-cities',
            'edit-cities',
            'delete-cities',

            // User Management
            'users-management',
            'create-users',
            'edit-users',
            'delete-users',

            // Role Management
            'roles-management',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Settings Management
            'settings-management',
            'edit-settings',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
