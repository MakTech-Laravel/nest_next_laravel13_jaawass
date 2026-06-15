<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Plan;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $currencyId = Currency::query()->where('code', 'USD')->value('id')
        ?? Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ])->id;

    if (! Plan::query()->exists()) {
        Plan::query()->insert([
            [
                'currency_id' => $currencyId,
                'name' => 'Professional',
                'description' => 'Growth plan',
                'button_text' => 'Start',
                'monthly_price' => 99,
                'yearly_price' => 990,
                'is_popular' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
});

test('public can fetch active promotion for pricing page without auth', function (): void {
    $promotion = Promotion::factory()->create([
        'plan_id' => Plan::query()->value('id'),
        'promotion_title' => 'Launch Offer',
        'status' => true,
    ]);

    $this->getJson('/api/v1/promotions/active')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $promotion->id)
        ->assertJsonPath('data.promotion_title', 'Launch Offer');
});
