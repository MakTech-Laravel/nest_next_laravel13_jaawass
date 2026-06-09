<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencyId = Currency::query()->where('code', config('currency.base_currency', 'USD'))->value('id')
            ?? Currency::query()->where('code', 'USD')->value('id');

        $plans = [
            [
                'currency_id' => $currencyId,
                'name' => 'Free',
                'description' => 'Get started with basic features',
                'button_text' => 'Get Started',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_popular' => false,
                'status' => true,
            ],
            [
                'currency_id' => $currencyId,
                'name' => 'Professional',
                'description' => 'For growing manufacturers',
                'button_text' => 'Get Started',
                'monthly_price' => 99,
                'yearly_price' => 990,
                'is_popular' => true,
                'status' => true,
            ],
            [
                'currency_id' => $currencyId,
                'name' => 'Enterprise',
                'description' => 'For established manufacturers',
                'button_text' => 'Get Started',
                'monthly_price' => 299,
                'yearly_price' => 2990,
                'is_popular' => false,
                'status' => true,
            ],
        ];

        Plan::insert($plans);

        $this->command->info('Plans seeded successfully!');
    }
}
