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
                'name' => 'Starter',
                'description' => 'For small manufacturers starting their export journey',
                'button_text' => 'Get Started',
                'monthly_price' => 149,
                'yearly_price' => 1490,
                'is_popular' => false,
                'status' => true,
            ],
            [
                'currency_id' => $currencyId,
                'name' => 'Growth',
                'description' => 'For established manufacturers seeking more exposure',
                'button_text' => 'Get Started',
                'monthly_price' => 299,
                'yearly_price' => 2990,
                'is_popular' => true,
                'status' => true,
            ],
            [
                'currency_id' => $currencyId,
                'name' => 'Enterprise',
                'description' => 'For large manufacturers with custom requirements',
                'button_text' => 'Get Started',
                'monthly_price' => 499,
                'yearly_price' => 4990,
                'is_popular' => false,
                'status' => true,
            ],
        ];

        Plan::insert($plans);

        $this->command->info('Plans seeded successfully!');
    }
}
