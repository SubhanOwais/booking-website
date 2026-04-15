<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Full_Name' => $this->faker->name(),
            'User_Name' => $this->faker->userName(),
            'Email' => $this->faker->unique()->safeEmail(),
            'Phone_Number' => $this->faker->phoneNumber(),
            'Password' => Hash::make('12345678'),
            'CNIC' => $this->faker->numerify('#####-#######-#'),
            'Address' => $this->faker->address(),
            'IsSuperAdmin' => false,
            'Is_Active' => true,
            'User_Type' => 'WebCustomer',
            'Created_On' => now(),
            'Changed_On' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
