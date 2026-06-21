<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Promotion;
use App\Services\Promotion\PromotionService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::query()->orderBy('id')->skip(1)->value('id')
                ?? Plan::query()->value('id')
                ?? 1,
            'slots' => PromotionService::DEFAULT_SLOTS,
            'duration_months' => PromotionService::DEFAULT_DURATION_MONTHS,
            'promotional_price' => PromotionService::DEFAULT_PROMOTIONAL_PRICE,
            'requires_payment' => false,
            'billing_period_unit' => 'month',
            'disclaimer_text' => PromotionService::DEFAULT_DISCLAIMER,
            'promotion_title' => fake()->sentence(3),
            'short_description' => fake()->sentence(),
            'button_text' => 'First 300 Only',
            'cta_button_text' => 'Apply Now',
            'highlight_text' => fake()->paragraph(),
            'expires_at' => null,
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
