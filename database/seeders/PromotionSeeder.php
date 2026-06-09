<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Promotion::create([
            'plan_id' => 2,
            'slots' => 300,
            'duration_months' => 6,
            'promotion_title' => 'Free Promotion',
            'short_description' => 'Get started with basic features',
            'button_text' => 'Get Started',
            'cta_button_text' => 'Get Started',
            'highlight_text' => 'Get started with basic features',
        ]);
    }
}
