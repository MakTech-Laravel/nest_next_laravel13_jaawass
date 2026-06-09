<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency_id' => static fn () => Currency::query()->where('code', 'USD')->firstOrFail()->id,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 1, 500),
            'quantity' => fake()->numberBetween(0, 1000),
            'image' => null,
            'status' => 'active',
        ];
    }
}
