<?php

namespace Database\Factories;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::BUYER->value,
            'agreed_to_terms' => true,
            'remember_token' => Str::random(10),
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

    public function manufacturer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::MANUFACTURER->value,
        ]);
    }

    public function manufacturerApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::MANUFACTURER->value,
            'manufacture_status' => UserManuFactureStatus::APPROVED,
            'manufacture_status_at' => now(),
            'manufacture_status_reason' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ADMIN->value,
        ]);
    }
}
