<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin
        User::create([
            'Full_Name' => 'Super Admin',
            'User_Name' => 'superadmin',
            'Email' => 'superadmin@example.com',
            'Phone_Number' => '03001234567',
            'Password' => Hash::make('12345678'),
            'CNIC' => '35202-1234567-1',
            'Address' => 'Lahore, Punjab, Pakistan',
            'IsSuperAdmin' => true,
            'Is_Active' => true,
            'User_Type' => 'SuperAdmin',
            'Created_On' => now(),
        ]);

        // Create WebCustomer
        User::create([
            'Full_Name' => 'Customer John',
            'User_Name' => 'customer',
            'Email' => 'customer@example.com',
            'Phone_Number' => '03001234569',
            'Password' => Hash::make('12345678'),
            'CNIC' => '35202-7654321-3',
            'Address' => 'Karachi, Sindh, Pakistan',
            'IsSuperAdmin' => false,
            'Is_Active' => true,
            'User_Type' => 'WebCustomer',
            'Created_On' => now(),
        ]);

        // Create more test customers
        User::factory(10)->create([
            'IsSuperAdmin' => false,
            'User_Type' => 'WebCustomer',
        ]);
    }
}
