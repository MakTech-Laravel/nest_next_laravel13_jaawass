<?php

use App\Enums\OrderStatus;
use App\Enums\RfqSubmissionStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\ManufacturerExportMarket;
use App\Models\Order;
use App\Models\Product;
use App\Models\RfqSubmission;
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

test('manufacturer export markets overview returns stats regions and suggestions', function (): void {
    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Export Co',
        'export_markets' => json_encode(['North America', 'Western Europe']),
    ]);

    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    Company::query()->create([
        'user_id' => $buyer->id,
        'company_name' => 'Buyer Co',
        'country' => 'United States',
    ]);

    $product = createMarketsProduct($manufacturer);
    $conversation = createMarketsConversation($buyer, $manufacturer);

    createMarketsRfq($buyer, $manufacturer, $product, $conversation, [
        'destination_country' => 'United States',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/markets');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'stats' => ['active_markets', 'total_inquiries', 'total_orders', 'growth_rate'],
                'active_regions',
                'suggestions',
                'meta' => ['regions', 'geographic_regions'],
            ],
        ])
        ->assertJsonPath('data.stats.total_inquiries', 0)
        ->assertJsonCount(2, 'data.active_regions');
});

test('manufacturer can add update and remove export market region', function (): void {
    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Export Co',
    ]);

    Passport::actingAs($manufacturer);

    $createResponse = $this->postJson('/api/v1/manufacturer/markets/regions', [
        'region' => 'North America',
        'country_codes' => ['US', 'CA'],
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.region', 'North America')
        ->assertJsonPath('data.country_codes', ['CA', 'US']);

    $marketId = $createResponse->json('data.id');

    $this->assertDatabaseHas('manufacturer_export_markets', [
        'id' => $marketId,
        'user_id' => $manufacturer->id,
        'region' => 'North America',
    ]);

    $manufacturer->refresh()->load('company');
    expect(json_decode($manufacturer->company->export_markets, true))->toBe(['North America']);

    $updateResponse = $this->putJson("/api/v1/manufacturer/markets/regions/{$marketId}", [
        'country_codes' => ['US'],
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.country_codes', ['US']);

    $deleteResponse = $this->deleteJson("/api/v1/manufacturer/markets/regions/{$marketId}");

    $deleteResponse->assertOk();

    $this->assertDatabaseMissing('manufacturer_export_markets', [
        'id' => $marketId,
    ]);
});

test('manufacturer can sync selected countries across regions', function (): void {
    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Export Co',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->putJson('/api/v1/manufacturer/markets/countries/sync', [
        'country_codes' => ['US', 'DE', 'FR'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.stats.active_markets', 3)
        ->assertJsonCount(2, 'data.active_regions');

    expect(
        ManufacturerExportMarket::query()
            ->where('user_id', $manufacturer->id)
            ->pluck('region')
            ->sort()
            ->values()
            ->all()
    )->toBe(['North America', 'Western Europe']);
});

test('manufacturer export markets countries endpoint returns full map catalog', function (): void {
    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Export Co',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/markets/countries?per_page=250');

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(190);

    $kenya = collect($response->json('data'))->firstWhere('code', 'KE');
    expect($kenya)->not->toBeNull()
        ->and($kenya['name'])->toBe('Kenya')
        ->and($kenya['export_market_region'])->toBe('Africa')
        ->and($kenya['geographic_region'])->toBe('Africa');
});

test('manufacturer can sync map-only countries such as kenya', function (): void {
    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Export Co',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->putJson('/api/v1/manufacturer/markets/countries/sync', [
        'country_codes' => ['KE', 'NG'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.stats.active_markets', 2)
        ->assertJsonCount(1, 'data.active_regions')
        ->assertJsonPath('data.active_regions.0.region', 'Africa');

    $this->assertDatabaseHas('manufacturer_export_market_countries', [
        'country_code' => 'KE',
        'country_name' => 'Kenya',
    ]);
});

test('manufacturer export markets countries endpoint supports search and selection state', function (): void {
    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Export Co',
    ]);

    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/manufacturer/markets/regions', [
        'region' => 'North America',
        'country_codes' => ['US'],
    ])->assertCreated();

    $response = $this->getJson('/api/v1/manufacturer/markets/countries?search=United');

    $response->assertOk()
        ->assertJsonPath('success', true);

    $items = collect($response->json('data'));

    expect($items->firstWhere('code', 'US')['is_selected'] ?? false)->toBeTrue();

    $canada = $items->firstWhere('code', 'CA');
    if ($canada !== null) {
        expect($canada['is_selected'])->toBeFalse();
    }
});

test('export markets overview counts inquiries and orders for active countries', function (): void {
    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Export Co',
    ]);

    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    Company::query()->create([
        'user_id' => $buyer->id,
        'company_name' => 'Buyer Co',
        'country' => 'Germany',
    ]);

    $product = createMarketsProduct($manufacturer);
    $conversation = createMarketsConversation($buyer, $manufacturer);

    Passport::actingAs($manufacturer);

    $this->putJson('/api/v1/manufacturer/markets/countries/sync', [
        'country_codes' => ['DE'],
    ])->assertOk();

    createMarketsRfq($buyer, $manufacturer, $product, $conversation, [
        'destination_country' => 'Germany',
    ]);

    Order::query()->create([
        'user_id' => $manufacturer->id,
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'title' => 'Test Order',
        'quantity' => 100,
        'quantity_unit' => 'pcs',
        'total_amount' => 5000,
        'currency_code' => 'USD',
        'destination' => 'Germany',
        'estimated_delivery_at' => now()->addWeek()->toDateString(),
        'status' => OrderStatus::OrderCreated->value,
    ]);

    $response = $this->getJson('/api/v1/manufacturer/markets');

    $response->assertOk()
        ->assertJsonPath('data.stats.total_inquiries', 1)
        ->assertJsonPath('data.stats.total_orders', 1)
        ->assertJsonPath('data.active_regions.0.inquiries', 1)
        ->assertJsonPath('data.active_regions.0.orders', 1);
});

test('export markets endpoints require export markets plan feature', function (): void {
    $manufacturer = manufacturerWithSubscription(features: [
        ['key' => 'export_markets_section', 'input_type' => 'boolean', 'value' => '0'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/markets')
        ->assertForbidden()
        ->assertJsonPath('data.feature', 'export_markets_section');
});

function createMarketsProduct(User $manufacturer): Product
{
    return Product::factory()->create([
        'user_id' => $manufacturer->id,
        'status' => 'active',
        'is_approved' => true,
    ]);
}

function createMarketsConversation(User $buyer, User $manufacturer): Conversation
{
    $conversation = Conversation::query()->create([
        'name' => 'Markets Conversation',
        'created_by' => $buyer->id,
    ]);

    $conversation->participants()->sync([$buyer->id, $manufacturer->id]);

    return $conversation;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createMarketsRfq(
    User $buyer,
    User $manufacturer,
    Product $product,
    Conversation $conversation,
    array $overrides = [],
): RfqSubmission {
    return RfqSubmission::query()->create(array_merge([
        'rfq_number' => 'RFQ-MK-'.uniqid(),
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'conversation_id' => $conversation->id,
        'quantity' => 100,
        'quantity_unit' => 'pcs',
        'status' => RfqSubmissionStatus::Pending->value,
    ], $overrides));
}
