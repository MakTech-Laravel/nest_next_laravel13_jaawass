<?php

namespace Database\Factories;

use App\Models\HelpCenterArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpCenterArticle>
 */
class HelpCenterArticleFactory extends Factory
{
    protected $model = HelpCenterArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'help_center_category_id' => 1,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'help_full' => 0,
            'not_help_full' => 0,
            'sort_order' => 1,
            'status' => true,
        ];
    }
}
