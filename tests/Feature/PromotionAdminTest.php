<?php

use App\Enums\PromotionUserStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Promotion\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    $currencyId = Currency::query()->where('code', 'USD')->value('id')
        ?? Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ])->id;

    if (Plan::query()->exists()) {
        return;
    }

    Plan::query()->insert([
        [
            'currency_id' => $currencyId,
            'name' => 'Free',
            'description' => 'Free plan',
            'button_text' => 'Start',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'is_popular' => false,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
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
});

function adminToken(): string
{
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);

    return $admin->createToken('test')->accessToken;
}

function createManufacturerWithCompany(): User
{
    $manufacturer = User::factory()->manufacturer()->create();

    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'TechVision Electronics',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shenzhen',
        'street_address' => '123 Main St',
        'phone' => '+86123456789',
        'zip_code' => '518000',
    ]);

    return $manufacturer;
}

test('admin can list promotions with enrollment stats', function (): void {
    $promotion = Promotion::factory()->create([
        'plan_id' => 2,
        'slots' => 10,
    ]);

    $manufacturer = createManufacturerWithCompany();

    $promotion->users()->attach($manufacturer->id, [
        'status' => PromotionUserStatus::ACCEPTED->value,
        'participated_at' => now(),
        'trial_ends_at' => now()->addMonths(6),
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->getJson('/api/v1/admin/promotions')
        ->assertOk()
        ->assertJsonPath('data.0.stats.accepted', 1)
        ->assertJsonPath('data.0.stats.spots_remaining', 9)
        ->assertJsonPath('data.0.approved', 1)
        ->assertJsonStructure([
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

test('promotion list is paginated and returns all promotions across pages', function (): void {
    Promotion::factory()->count(3)->create(['plan_id' => 2]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->getJson('/api/v1/admin/promotions?per_page=2&page=1')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.last_page', 2);

    $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->getJson('/api/v1/admin/promotions?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('reset deactivates existing promotions and creates a new active promotion with plan 2 and 300 slots', function (): void {
    $old = Promotion::factory()->create([
        'plan_id' => 2,
        'slots' => 50,
        'status' => true,
    ]);

    /** @var TestCase $this */
    $response = $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->postJson('/api/v1/admin/promotions/reset')
        ->assertCreated()
        ->assertJsonPath('data.promotion.plan.id', 2)
        ->assertJsonPath('data.promotion.slots', PromotionService::DEFAULT_SLOTS)
        ->assertJsonPath('data.promotion.status', true)
        ->assertJsonPath('data.promotion.stats.accepted', 0)
        ->assertJsonPath('data.promotion.stats.spots_remaining', 300);

    $old->refresh();

    expect($old->status)->toBeFalse();
    expect(Promotion::query()->where('status', true)->count())->toBe(1);
    expect($response->json('data.promotion.id'))->not->toBe($old->id);
});

test('admin can update promotion and only one can stay active', function (): void {
    $first = Promotion::factory()->create(['plan_id' => 2, 'status' => true]);
    $second = Promotion::factory()->inactive()->create(['plan_id' => 2]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->putJson("/api/v1/admin/promotions/{$second->id}", [
            'status' => true,
            'promotion_title' => 'Updated Title',
        ])
        ->assertOk()
        ->assertJsonPath('data.promotion_title', 'Updated Title');

    expect($first->fresh()->status)->toBeFalse();
    expect($second->fresh()->status)->toBeTrue();
});

test('enroll rejects when promotion is full', function (): void {
    $promotion = Promotion::factory()->create([
        'plan_id' => 2,
        'slots' => 1,
    ]);

    $existing = createManufacturerWithCompany();
    $promotion->users()->attach($existing->id, [
        'status' => PromotionUserStatus::ACCEPTED->value,
        'participated_at' => now(),
    ]);

    $newManufacturer = createManufacturerWithCompany();

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->postJson("/api/v1/admin/promotions/{$promotion->id}/enroll", [
            'user_id' => $newManufacturer->id,
            'status' => PromotionUserStatus::ACCEPTED->value,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('promotion.full'));
});

test('admin can enroll manufacturer and approve participant', function (): void {
    $promotion = Promotion::factory()->create([
        'plan_id' => 2,
        'slots' => 5,
    ]);

    $manufacturer = createManufacturerWithCompany();

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->postJson("/api/v1/admin/promotions/{$promotion->id}/enroll", [
            'user_id' => $manufacturer->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', PromotionUserStatus::PENDING->value);

    $this->withHeader('Authorization', 'Bearer '.adminToken())
        ->patchJson("/api/v1/admin/promotions/{$promotion->id}/participants/{$manufacturer->id}", [
            'status' => PromotionUserStatus::ACCEPTED->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', PromotionUserStatus::ACCEPTED->value)
        ->assertJsonPath('data.trial_ends_at', fn ($value) => $value !== null);
});
