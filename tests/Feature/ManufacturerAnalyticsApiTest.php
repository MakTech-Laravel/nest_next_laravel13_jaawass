<?php

use App\Enums\DashboardEventType;
use App\Enums\RfqSubmissionStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\DashboardEvent;
use App\Models\Industry;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\RfqSubmission;
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

test('manufacturer analytics metrics endpoint returns four metric cards', function (): void {
    $manufacturer = manufacturerWithSubscription();
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    Company::query()->create([
        'user_id' => $buyer->id,
        'company_name' => 'Buyer Co',
        'country' => 'United States',
    ]);

    $product = createAnalyticsProduct($manufacturer);

    DashboardEvent::query()->create([
        'actor_user_id' => $buyer->id,
        'counterparty_user_id' => $manufacturer->id,
        'role_context' => 'buyer',
        'event_type' => DashboardEventType::ProductViewed->value,
        'entity_type' => 'product',
        'entity_id' => $product->id,
        'occurred_at' => now(),
    ]);

    DashboardEvent::query()->create([
        'actor_user_id' => $buyer->id,
        'counterparty_user_id' => $manufacturer->id,
        'role_context' => 'buyer',
        'event_type' => DashboardEventType::SupplierViewed->value,
        'entity_type' => 'supplier',
        'entity_id' => $manufacturer->id,
        'occurred_at' => now(),
    ]);

    $conversation = createAnalyticsConversation($buyer, $manufacturer);

    createAnalyticsRfq($buyer, $manufacturer, $product, $conversation, [
        'status' => RfqSubmissionStatus::Quoted->value,
        'quoted_at' => now(),
        'quoted_price' => 5000,
    ]);

    Message::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $buyer->id,
        'body' => 'Hello manufacturer',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/analytics/metrics?period=last_30_days');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'period',
                'date_from',
                'date_to',
                'metrics' => [
                    '*' => ['key', 'label', 'value', 'raw_value', 'change', 'trend'],
                ],
            ],
        ])
        ->assertJsonCount(4, 'data.metrics')
        ->assertJsonPath('data.metrics.0.key', 'profile_views')
        ->assertJsonPath('data.metrics.0.raw_value', 2)
        ->assertJsonPath('data.metrics.1.key', 'inquiries_received')
        ->assertJsonPath('data.metrics.1.raw_value', 1)
        ->assertJsonPath('data.metrics.2.key', 'messages')
        ->assertJsonPath('data.metrics.2.raw_value', 1)
        ->assertJsonPath('data.metrics.3.key', 'quote_requests')
        ->assertJsonPath('data.metrics.3.raw_value', 1);
});

test('manufacturer analytics performance endpoint supports pagination', function (): void {
    $manufacturer = manufacturerWithSubscription();

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/analytics/performance?period=last_7_days&per_page=3&page=1');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => ['name', 'period', 'date_from', 'date_to', 'profile_views', 'inquiries', 'messages', 'quote_requests'],
            ],
            'links',
            'meta',
        ]);
});

test('manufacturer analytics products endpoint returns top products with views and inquiries', function (): void {
    $manufacturer = manufacturerWithSubscription();
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $product = createAnalyticsProduct($manufacturer, 'Analytics Widget');

    DashboardEvent::query()->create([
        'actor_user_id' => $buyer->id,
        'counterparty_user_id' => $manufacturer->id,
        'role_context' => 'buyer',
        'event_type' => DashboardEventType::ProductViewed->value,
        'entity_type' => 'product',
        'entity_id' => $product->id,
        'occurred_at' => now(),
    ]);

    $conversation = createAnalyticsConversation($buyer, $manufacturer);

    createAnalyticsRfq($buyer, $manufacturer, $product, $conversation, [
        'quantity' => 50,
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/analytics/products?period=last_30_days&search=Analytics');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.name', 'Analytics Widget')
        ->assertJsonPath('data.0.views', 1)
        ->assertJsonPath('data.0.inquiries', 1);
});

test('manufacturer analytics countries endpoint returns buyer location distribution', function (): void {
    $manufacturer = manufacturerWithSubscription();
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    Company::query()->create([
        'user_id' => $buyer->id,
        'company_name' => 'US Buyer',
        'country' => 'United States',
    ]);

    $product = createAnalyticsProduct($manufacturer);

    $conversation = createAnalyticsConversation($buyer, $manufacturer);

    createAnalyticsRfq($buyer, $manufacturer, $product, $conversation);

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/analytics/countries?period=last_30_days');

    $response->assertOk()
        ->assertJsonPath('success', true);

    $countries = collect($response->json('data'));
    $unitedStates = $countries->firstWhere('country', 'United States');

    expect($unitedStates)->not->toBeNull()
        ->and($unitedStates['country_code'])->toBe('US')
        ->and($unitedStates['raw_buyers'])->toBe(1)
        ->and((float) $unitedStates['percentage'])->toBe(100.0);
});

test('manufacturer analytics funnel endpoint returns conversion steps', function (): void {
    $manufacturer = manufacturerWithSubscription();
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $product = createAnalyticsProduct($manufacturer);

    DashboardEvent::query()->create([
        'actor_user_id' => $buyer->id,
        'counterparty_user_id' => $manufacturer->id,
        'role_context' => 'buyer',
        'event_type' => DashboardEventType::ProductViewed->value,
        'entity_type' => 'product',
        'entity_id' => $product->id,
        'occurred_at' => now(),
    ]);

    $conversation = createAnalyticsConversation($buyer, $manufacturer);

    DashboardEvent::query()->create([
        'actor_user_id' => $buyer->id,
        'counterparty_user_id' => $manufacturer->id,
        'role_context' => 'buyer',
        'event_type' => DashboardEventType::MessageSent->value,
        'entity_type' => 'conversation',
        'entity_id' => $conversation->id,
        'occurred_at' => now(),
    ]);

    createAnalyticsRfq($buyer, $manufacturer, $product, $conversation, [
        'status' => RfqSubmissionStatus::Quoted->value,
        'quoted_at' => now(),
        'quoted_price' => 1000,
    ]);

    Order::query()->create([
        'user_id' => $manufacturer->id,
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'title' => 'Analytics order',
        'quantity' => 10,
        'total_amount' => 1000,
        'currency_code' => 'USD',
        'estimated_delivery_at' => now()->addWeek()->toDateString(),
        'status' => 'order_created',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/analytics/funnel?period=last_30_days');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(4, 'data.steps')
        ->assertJsonPath('data.steps.0.key', 'profile_views')
        ->assertJsonPath('data.steps.0.value', 1)
        ->assertJsonPath('data.steps.1.key', 'messages_started')
        ->assertJsonPath('data.steps.1.value', 1)
        ->assertJsonPath('data.steps.2.key', 'quotes_sent')
        ->assertJsonPath('data.steps.2.value', 1)
        ->assertJsonPath('data.steps.3.key', 'orders_received')
        ->assertJsonPath('data.steps.3.value', 1);
});

test('advanced analytics endpoints require advanced_analytics feature', function (): void {
    $manufacturer = manufacturerWithSubscription(features: [
        ['key' => 'basic_analytics', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/analytics/metrics')->assertOk();
    $this->getJson('/api/v1/manufacturer/analytics/products')->assertOk();
    $this->getJson('/api/v1/manufacturer/analytics/performance')->assertForbidden();
    $this->getJson('/api/v1/manufacturer/analytics/countries')->assertForbidden();
    $this->getJson('/api/v1/manufacturer/analytics/funnel')->assertForbidden();
});

test('manufacturer analytics requires authentication', function (): void {
    $this->getJson('/api/v1/manufacturer/analytics/metrics')->assertUnauthorized();
});

function createAnalyticsProduct(User $manufacturer, string $name = 'Analytics Product'): Product
{
    $industry = Industry::query()->create([
        'name' => 'Analytics Industry',
        'slug' => 'analytics-industry-'.uniqid(),
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Analytics Sub',
        'slug' => 'analytics-sub-'.uniqid(),
    ]);

    return Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => $name,
        'slug' => 'analytics-product-'.uniqid(),
        'description' => 'Analytics test product',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'is_approved' => true,
    ]);
}

function createAnalyticsConversation(User $buyer, User $manufacturer): Conversation
{
    $conversation = Conversation::query()->create([
        'name' => 'Analytics Conversation',
        'created_by' => $buyer->id,
    ]);

    $conversation->participants()->sync([$buyer->id, $manufacturer->id]);

    return $conversation;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createAnalyticsRfq(
    User $buyer,
    User $manufacturer,
    Product $product,
    Conversation $conversation,
    array $overrides = [],
): RfqSubmission {
    return RfqSubmission::query()->create(array_merge([
        'rfq_number' => 'RFQ-AN-'.uniqid(),
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'conversation_id' => $conversation->id,
        'quantity' => 100,
        'quantity_unit' => 'pcs',
        'status' => RfqSubmissionStatus::Pending->value,
    ], $overrides));
}
