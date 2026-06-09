<?php

namespace Database\Factories;

use App\Enums\QuickFilterType;
use App\Models\QuickFilterOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuickFilterOption>
 */
class QuickFilterOptionFactory extends Factory
{
    protected $model = QuickFilterOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->country();

        return [
            'type' => QuickFilterType::Countries,
            'display_label' => $label,
            'value' => Str::slug($label).'-'.fake()->unique()->numerify('####'),
            'is_enabled' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function ofType(QuickFilterType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'display_label' => fake()->words(2, true),
            'value' => Str::slug(fake()->unique()->word()).'-'.fake()->numerify('###'),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
