<?php

declare(strict_types=1);

use App\Enums\Api\V1\ProductStatusEnum;
use App\Models\Conversation;
use App\Models\Currency;
use App\Models\Industry;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('manufacturer product stats returns aggregates and buyer-initiated conversation count', function (): void {
    $currency = Currency::query()->where('code', 'USD')->firstOrFail();

    $industry = Industry::query()->create([
        'name' => 'Industry',
        'description' => null,
        'slug' => 'industry-'.uniqid(),
        'icon' => null,
        'color' => null,
        'featured' => false,
        'sort_order' => 0,
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Sub',
        'slug' => 'sub-'.uniqid(),
        'icon' => null,
        'sort_order' => 0,
    ]);

    $manufacturer = User::factory()->manufacturerApproved()->create();
    $buyer = User::factory()->create();

    $baseProduct = [
        'user_id' => $manufacturer->id,
        'name' => 'Widget',
        'description' => 'Desc',
        'slug' => 'widget-'.uniqid(),
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'currency_id' => $currency->id,
        'keywords' => null,
        'status' => ProductStatusEnum::ACTIVE->value,
        'view_count' => 10,
    ];

    Product::query()->create([...$baseProduct, 'slug' => 'w-a-'.uniqid(), 'view_count' => 100]);
    Product::query()->create([...$baseProduct, 'slug' => 'w-b-'.uniqid(), 'view_count' => 200, 'status' => ProductStatusEnum::DRAFT->value]);

    $otherManufacturer = User::factory()->manufacturerApproved()->create();
    Product::query()->create([
        'user_id' => $otherManufacturer->id,
        'name' => 'Other',
        'description' => 'x',
        'slug' => 'other-'.uniqid(),
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'currency_id' => $currency->id,
        'keywords' => null,
        'status' => ProductStatusEnum::ACTIVE->value,
        'view_count' => 9999,
    ]);

    $buyerStarted = Conversation::factory()->create(['created_by' => $buyer->id]);
    $buyerStarted->participants()->attach([$buyer->id, $manufacturer->id]);

    $mfgStarted = Conversation::factory()->create(['created_by' => $manufacturer->id]);
    $mfgStarted->participants()->attach([$buyer->id, $manufacturer->id]);

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/products/stats');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_products', 2)
        ->assertJsonPath('data.active_products', 1)
        ->assertJsonPath('data.total_views', 300)
        ->assertJsonPath('data.total_inquiries', 1);
});
