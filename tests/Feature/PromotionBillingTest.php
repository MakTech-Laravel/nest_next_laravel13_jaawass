<?php

use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\PromotionUserStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    $currencyId = \App\Models\Currency::query()->where('code', 'USD')->value('id')
        ?? \App\Models\Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ])->id;

    if (! \App\Models\Plan::query()->exists()) {
        \App\Models\Plan::query()->insert([
            [
                'currency_id' => $currencyId,
                'name' => 'Starter',
                'description' => 'Starter plan',
                'button_text' => 'Start',
                'monthly_price' => 149,
                'yearly_price' => 1490,
                'is_popular' => false,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'currency_id' => $currencyId,
                'name' => 'Growth',
                'description' => 'Growth plan',
                'button_text' => 'Start',
                'monthly_price' => 299,
                'yearly_price' => 2990,
                'is_popular' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
});

test('active promotion exposes customizable billing period and promotional price', function (): void {
    $growthPlanId = \App\Models\Plan::query()->orderBy('id')->skip(1)->value('id')
        ?? \App\Models\Plan::query()->value('id');

    $promotion = Promotion::factory()->create([
        'plan_id' => $growthPlanId,
        'duration_months' => 1,
        'billing_period_unit' => 'year',
        'promotional_price' => 0,
        'requires_payment' => false,
        'disclaimer_text' => 'Subject to admin review and approval.',
    ]);

    /** @var TestCase $this */
    $this->getJson('/api/v1/promotions/active')
        ->assertOk()
        ->assertJsonPath('data.promotional_price', '0.00')
        ->assertJsonPath('data.requires_payment', false)
        ->assertJsonPath('data.billing_period.value', 1)
        ->assertJsonPath('data.billing_period.unit', 'year')
        ->assertJsonPath('data.billing_period.label', '1 year')
        ->assertJsonPath('data.disclaimer_text', 'Subject to admin review and approval.');
});

test('manufacturer can apply to active founding promotion', function (): void {
    Promotion::factory()->create([
        'plan_id' => \App\Models\Plan::query()->orderBy('id')->skip(1)->value('id')
            ?? \App\Models\Plan::query()->value('id'),
        'status' => true,
    ]);

    $manufacturer = User::factory()->manufacturer()->create();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Apply Co',
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $this->postJson('/api/v1/manufacturer/promotions/apply')
        ->assertCreated()
        ->assertJsonPath('data.application.status', PromotionUserStatus::PENDING->value);

    $this->assertDatabaseHas('promotion_user', [
        'user_id' => $manufacturer->id,
        'status' => PromotionUserStatus::PENDING->value,
    ]);
});

test('accepting promotion participant grants trialing subscription without payment', function (): void {
    $growthPlanId = \App\Models\Plan::query()->orderBy('id')->skip(1)->value('id')
        ?? \App\Models\Plan::query()->value('id');

    $promotion = Promotion::factory()->create([
        'plan_id' => $growthPlanId,
        'duration_months' => 6,
        'billing_period_unit' => 'month',
        'promotional_price' => 0,
    ]);

    $manufacturer = User::factory()->manufacturer()->create();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Trial Co',
    ]);

    $promotion->users()->attach($manufacturer->id, [
        'status' => PromotionUserStatus::PENDING->value,
        'participated_at' => now(),
    ]);

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->patchJson("/api/v1/admin/promotions/{$promotion->id}/participants/{$manufacturer->id}", [
            'status' => PromotionUserStatus::ACCEPTED->value,
        ])
        ->assertOk();

    $this->assertDatabaseHas('subscriptions', [
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $growthPlanId,
        'status' => SubscriptionStatus::TRIALING->value,
        'billing_interval' => 'month',
    ]);

    $manufacturer->refresh()->load('subscription');
    expect($manufacturer->subscription?->trial_ends_at)->not->toBeNull();
});
