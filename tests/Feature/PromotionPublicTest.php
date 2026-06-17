<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
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
    $plan = Plan::query()->firstOrFail();

    $feature = Feature::query()->create([
        'name' => 'Product listings',
        'key' => 'product_listings',
    ]);

    PlanFeature::query()->create([
        'plan_id' => $plan->id,
        'feature_id' => $feature->id,
        'input_type' => 'number',
        'value' => '500',
    ]);

    $promotion = Promotion::factory()->create([
        'plan_id' => $plan->id,
        'promotion_title' => 'Launch Offer',
        'status' => true,
        'slots' => 300,
        'duration_months' => 6,
    ]);

    $this->getJson('/api/v1/promotions/active')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $promotion->id)
        ->assertJsonPath('data.promotion_title', 'Launch Offer')
        ->assertJsonPath('data.plan.id', $plan->id)
        ->assertJsonPath('data.plan.monthly_price.amount', '99.00')
        ->assertJsonPath('data.plan.yearly_price.amount', '990.00')
        ->assertJsonPath('data.plan.features.0.value', '500')
        ->assertJsonPath('data.plan.features.0.features.name', 'Product listings')
        ->assertJsonPath('data.stats.slots_total', 300)
        ->assertJsonPath('data.spots_remaining', 300);
});

test('public active promotion endpoint returns 404 when none are active', function (): void {
    Promotion::factory()->inactive()->create([
        'plan_id' => Plan::query()->value('id'),
    ]);

    $this->getJson('/api/v1/promotions/active')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null);
});

test('public active promotion endpoint ignores expired promotions', function (): void {
    Promotion::factory()->create([
        'plan_id' => Plan::query()->value('id'),
        'status' => true,
        'expires_at' => now()->subDay(),
    ]);

    $this->getJson('/api/v1/promotions/active')
        ->assertNotFound();
});
