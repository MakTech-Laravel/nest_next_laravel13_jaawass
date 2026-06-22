<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'product_id' => 1,
            'order_id' => 1,
            'reviewer_id' => 1,
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(4),
            'comment' => fake()->paragraph(),
            'status' => ReviewStatus::PENDING->value,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::PUBLISHED->value,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::HIDDEN->value,
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::FLAGGED->value,
        ]);
    }
}
