<?php

namespace Database\Factories;

use App\Models\Industry;
use App\Models\Product;
use App\Models\SubCategory;
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
            'user_id' => User::factory()->manufacturer(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'status' => 'active',
            'is_approved' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Product $product): void {
            if ($product->industry_id !== null && $product->sub_category_id !== null) {
                return;
            }

            $industry = Industry::query()->create([
                'name' => fake()->words(2, true),
                'slug' => fake()->unique()->slug(),
            ]);

            $subCategory = SubCategory::query()->create([
                'industry_id' => $industry->id,
                'name' => fake()->words(2, true),
                'slug' => fake()->unique()->slug(),
            ]);

            $product->industry_id ??= $industry->id;
            $product->sub_category_id ??= $subCategory->id;
        });
    }
}
