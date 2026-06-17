<?php

use App\Enums\DashboardEventType;
use App\Enums\OrderStatus;
use App\Enums\RfqSubmissionStatus;
use App\Models\Conversation;
use App\Models\DashboardEvent;
use App\Models\Industry;
use App\Models\Order;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        \Database\Seeders\LanguageSeeder::class,
        \Database\Seeders\CurrencySeeder::class,
        \Database\Seeders\UserSeeder::class,
        \Database\Seeders\ManufacturerCompanySeeder::class,
        \Database\Seeders\IndustrySeeder::class,
        \Database\Seeders\SubCategorySeeder::class,
    ]);
});

test('buyer dashboard overview returns stats and sections', function (): void {
    $buyer = User::query()->where('email', 'user@dev.com')->firstOrFail();
    Passport::actingAs($buyer);

    $response = $this->getJson('/api/v1/buyer/dashboard');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'welcome' => ['first_name', 'name'],
                'stats' => [
                    'active_conversations',
                    'rfqs_submitted',
                    'saved_suppliers',
                    'products_viewed',
                ],
                'recent_messages',
                'recent_rfqs',
                'recommended_suppliers',
                'recent_activity',
            ],
        ]);
});

test('manufacturer dashboard overview returns stats and sections', function (): void {
    $manufacturer = User::query()->where('email', 'manufacturer@dev.com')->firstOrFail();
    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/dashboard');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'profile_completeness' => ['percent', 'label'],
                'stats',
                'recent_inquiries',
                'response_metrics' => ['response_rate', 'quote_conversion', 'on_time_delivery'],
                'quick_stats',
                'recent_activity',
            ],
        ]);
});

test('admin dashboard overview returns stats and pending approvals', function (): void {
    $admin = User::query()->where('email', 'admin@dev.com')->firstOrFail();
    Passport::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/dashboard');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'stats',
                'pending_approvals',
                'recent_reports',
                'recent_activity',
            ],
        ]);
});

test('buyer cannot access manufacturer dashboard', function (): void {
    $buyer = User::query()->where('email', 'user@dev.com')->firstOrFail();
    Passport::actingAs($buyer);

    $this->getJson('/api/v1/manufacturer/dashboard')->assertForbidden();
});

test('manufacturer dashboard computes response time and on-time delivery dynamically', function (): void {
    $manufacturer = User::query()->where('email', 'manufacturer@dev.com')->firstOrFail();
    $buyer = User::query()->where('email', 'user@dev.com')->firstOrFail();
    $product = createDashboardTestProduct($manufacturer);
    $conversation = createDashboardConversation($buyer, $manufacturer);

    $rfq = RfqSubmission::query()->create([
        'rfq_number' => 'RFQ-DASH-1',
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'conversation_id' => $conversation->id,
        'quantity' => 500,
        'quantity_unit' => 'piece',
        'status' => RfqSubmissionStatus::Quoted->value,
        'created_at' => now()->subHours(6),
        'updated_at' => now()->subHours(1),
        'first_manufacturer_response_at' => now()->subHours(3),
    ]);

    Order::query()->create([
        'user_id' => $manufacturer->id,
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'title' => 'On time order',
        'quantity' => 100,
        'total_amount' => 1000,
        'currency_code' => 'USD',
        'estimated_delivery_at' => now()->toDateString(),
        'delivered_at' => now()->subHour(),
        'status' => OrderStatus::Completed->value,
    ]);

    Order::query()->create([
        'user_id' => $manufacturer->id,
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'title' => 'Late order',
        'quantity' => 120,
        'total_amount' => 1600,
        'currency_code' => 'USD',
        'estimated_delivery_at' => now()->subDay()->toDateString(),
        'delivered_at' => now(),
        'status' => OrderStatus::Completed->value,
    ]);

    Passport::actingAs($manufacturer);
    $response = $this->getJson('/api/v1/manufacturer/dashboard');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.response_metrics.on_time_delivery', 50)
        ->assertJsonPath('data.quick_stats.avg_response_time_seconds', 10800);
});

test('buyer dashboard uses tracked product views', function (): void {
    $buyer = User::query()->where('email', 'user@dev.com')->firstOrFail();
    $manufacturer = User::query()->where('email', 'manufacturer@dev.com')->firstOrFail();
    $product = createDashboardTestProduct($manufacturer);

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
        'event_type' => DashboardEventType::ProductViewed->value,
        'entity_type' => 'product',
        'entity_id' => $product->id,
        'occurred_at' => now()->addMinute(),
    ]);

    Passport::actingAs($buyer);
    $response = $this->getJson('/api/v1/buyer/dashboard');
    $response->assertOk()
        ->assertJsonPath('data.stats.products_viewed.value', 1);
});

test('dashboard event backfill seeder is idempotent', function (): void {
    $buyer = User::query()->where('email', 'user@dev.com')->firstOrFail();
    $supplier = User::query()->where('email', 'manufacturer@dev.com')->firstOrFail();
    $saved = \App\Models\SaveSupplier::query()->firstOrCreate([
        'user_id' => $buyer->id,
        'supplier_id' => $supplier->id,
    ]);
    $saved->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

    $this->seed(\Database\Seeders\DashboardEventBackfillSeeder::class);
    $firstCount = DashboardEvent::query()->count();

    $this->seed(\Database\Seeders\DashboardEventBackfillSeeder::class);
    $secondCount = DashboardEvent::query()->count();

    expect($secondCount)->toBe($firstCount);
});

function createDashboardTestProduct(User $manufacturer): Product
{
    $industry = Industry::query()->first() ?? Industry::query()->create([
        'name' => 'Dashboard Industry',
        'slug' => 'dashboard-industry-'.uniqid(),
    ]);
    $subCategory = SubCategory::query()->where('industry_id', $industry->id)->first()
        ?? SubCategory::query()->create([
            'industry_id' => $industry->id,
            'name' => 'Dashboard Subcategory',
            'slug' => 'dashboard-subcategory-'.uniqid(),
        ]);

    return Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Dashboard Product '.uniqid(),
        'slug' => 'dashboard-product-'.uniqid(),
        'description' => 'Dashboard tracking product',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'is_approved' => true,
        'view_count' => 10,
        'inquiry_count' => 2,
    ]);
}

function createDashboardConversation(User $buyer, User $manufacturer): Conversation
{
    $conversation = Conversation::query()->create([
        'name' => 'Dashboard Conversation',
        'created_by' => $buyer->id,
    ]);

    $conversation->participants()->sync([$buyer->id, $manufacturer->id]);

    return $conversation;
}
