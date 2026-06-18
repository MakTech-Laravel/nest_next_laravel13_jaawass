<?php

declare(strict_types=1);

use App\Enums\RfqSubmissionStatus;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

function seedProductForManufacturer(User $manufacturer): Product
{
    $currencyId = (int) (DB::table('currencies')->where('code', 'USD')->value('id') ?? 0);

    if ($currencyId === 0) {
        $currencyId = DB::table('currencies')->insertGetId([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $industryId = DB::table('industries')->insertGetId([
        'name' => 'Consumer Electronics',
        'slug' => 'consumer-electronics',
        'description' => 'Consumer electronics industry',
        'featured' => false,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subCategoryId = DB::table('sub_categories')->insertGetId([
        'industry_id' => $industryId,
        'name' => 'Wireless Earbuds',
        'slug' => 'wireless-earbuds',
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $productId = DB::table('products')->insertGetId([
        'user_id' => $manufacturer->id,
        'currency_id' => $currencyId,
        'name' => 'TWS Earbuds Pro',
        'description' => 'Noise cancelling earbuds',
        'slug' => 'tws-earbuds-pro',
        'industry_id' => $industryId,
        'sub_category_id' => $subCategoryId,
        'view_count' => 0,
        'inquiry_count' => 0,
        'status' => 'active',
        'is_approved' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Product::query()->findOrFail($productId);
}

test('buyer can submit rfq and product inquiry count increments', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    DB::table('companies')->insert([
        'user_id' => $manufacturer->id,
        'company_name' => 'TechVision Electronics',
        'country' => 'China',
        'city' => 'Shenzhen',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Passport::actingAs($buyer);

    $response = $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 5000,
        'quantity_unit' => 'pieces',
        'target_price' => 15,
        'target_currency_code' => 'usd',
        'shipping_terms' => 'FOB',
        'destination_country' => 'Bangladesh',
        'destination_port_city' => 'Chattogram',
        'packaging_details' => 'Standard Packaging',
        'additional_requirements' => 'OEM logo required',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.rfq_number', 'RFQ-001')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.product.id', $product->id)
        ->assertJsonPath('data.product.inquiry_count', 1)
        ->assertJsonPath('data.supplier.company_name', 'TechVision Electronics');

    expect(RfqSubmission::query()->count())->toBe(1)
        ->and(Product::query()->findOrFail($product->id)->inquiry_count)->toBe(1)
        ->and(Conversation::query()->count())->toBe(1);
});

test('buyer dashboard rfq api shows own rfqs with conversation id', function (): void {
    $buyer = User::factory()->create();
    $otherBuyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    Passport::actingAs($buyer);

    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 2000,
    ])->assertCreated();

    Passport::actingAs($otherBuyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 1000,
    ])->assertCreated();

    Passport::actingAs($buyer);
    $response = $this->getJson('/api/v1/buyer/rfqs');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'rfq_number',
                    'conversation_id',
                    'product' => ['id', 'name'],
                    'supplier' => ['id'],
                    'message_endpoint',
                ],
            ],
        ]);
});

test('buyer can update own rfq status', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 750,
    ])->assertCreated();

    $rfq = RfqSubmission::query()->firstOrFail();

    $response = $this->patchJson("/api/v1/buyer/rfqs/{$rfq->id}/status", [
        'status' => 'in_review',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $rfq->id)
        ->assertJsonPath('data.status', 'in_review');

    expect($rfq->fresh()->status)->toBe(RfqSubmissionStatus::InReview);
});

test('buyer rfq counts api returns dashboard counters', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    Passport::actingAs($buyer);

    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 1000,
    ])->assertCreated();

    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 1500,
    ])->assertCreated();

    $firstRfq = RfqSubmission::query()->orderBy('id')->firstOrFail();
    $secondRfq = RfqSubmission::query()->orderByDesc('id')->firstOrFail();

    $this->patchJson("/api/v1/buyer/rfqs/{$firstRfq->id}/status", [
        'status' => 'quoted',
    ])->assertOk();

    $this->patchJson("/api/v1/buyer/rfqs/{$secondRfq->id}/status", [
        'status' => 'in_review',
    ])->assertOk();

    $response = $this->getJson('/api/v1/buyer/rfqs/counts');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_rfqs', 2)
        ->assertJsonPath('data.quoted', 1)
        ->assertJsonPath('data.pending', 0)
        ->assertJsonPath('data.in_review', 1)
        ->assertJsonPath('data.accepted', 0)
        ->assertJsonPath('data.cancelled', 0)
        ->assertJsonPath('data.expired', 0);
});

test('buyer can search rfqs and filter by status', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    DB::table('companies')->insert([
        'user_id' => $manufacturer->id,
        'company_name' => 'Acme Supplier',
        'country' => 'China',
        'city' => 'Guangzhou',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Passport::actingAs($buyer);

    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 1000,
    ])->assertCreated();

    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 3000,
    ])->assertCreated();

    $firstRfq = RfqSubmission::query()->orderBy('id')->firstOrFail();
    $this->patchJson("/api/v1/buyer/rfqs/{$firstRfq->id}/status", [
        'status' => 'quoted',
    ])->assertOk();

    $response = $this->getJson('/api/v1/buyer/rfqs/search?search=RFQ-001&status=quoted');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rfq_number', 'RFQ-001')
        ->assertJsonPath('data.0.status', 'quoted');
});

test('buyer can show own rfq details', function (): void {
    $buyer = User::factory()->create();
    $otherBuyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 450,
    ])->assertCreated();

    $rfq = RfqSubmission::query()->firstOrFail();

    $response = $this->getJson("/api/v1/buyer/rfqs/{$rfq->id}");
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $rfq->id)
        ->assertJsonPath('data.product.id', $product->id);

    Passport::actingAs($otherBuyer);
    $this->getJson("/api/v1/buyer/rfqs/{$rfq->id}")
        ->assertNotFound();
});

test('admin can list and show rfqs', function (): void {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    DB::table('companies')->insert([
        'user_id' => $manufacturer->id,
        'company_name' => 'Zenith Manufacturing',
        'country' => 'China',
        'city' => 'Shenzhen',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 900,
    ])->assertCreated();

    $rfq = RfqSubmission::query()->firstOrFail();

    Passport::actingAs($admin);

    $listResponse = $this->getJson('/api/v1/admin/rfqs?search=RFQ-001&status=pending');
    $listResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $rfq->id)
        ->assertJsonPath('data.0.status', 'pending');

    $showResponse = $this->getJson("/api/v1/admin/rfqs/{$rfq->id}");
    $showResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $rfq->id)
        ->assertJsonPath('data.buyer.id', $buyer->id)
        ->assertJsonPath('data.product.id', $product->id);
});

test('manufacturer can reply and send quote then buyer can accept quote', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 5000,
    ])->assertCreated();

    $rfq = RfqSubmission::query()->firstOrFail();

    Passport::actingAs($manufacturer);
    $this->postJson("/api/v1/manufacturer/rfqs/{$rfq->id}/reply", [
        'manufacturer_reply' => 'Thanks, we are checking your requirements.',
    ])->assertOk()
        ->assertJsonPath('data.status', 'in_review');

    $this->postJson("/api/v1/manufacturer/rfqs/{$rfq->id}/quote", [
        'quoted_price' => 16.5,
        'quote_currency_code' => 'usd',
        'minimum_order_quantity' => 1000,
        'lead_time_days' => 30,
        'quote_valid_until' => now()->addDays(7)->toDateString(),
        'manufacturer_reply' => 'FOB Shenzhen, 1% spare units included.',
    ])->assertOk()
        ->assertJsonPath('data.status', 'quoted')
        ->assertJsonPath('data.quote_currency_code', 'USD')
        ->assertJsonPath('data.minimum_order_quantity', 1000);

    Passport::actingAs($buyer);
    $this->postJson("/api/v1/buyer/rfqs/{$rfq->id}/respond-quote", [
        'action' => 'accept',
    ])->assertOk()
        ->assertJsonPath('data.status', 'accepted');

    expect($rfq->fresh()->status)->toBe(RfqSubmissionStatus::Accepted);
});

test('buyer can cancel quoted rfq and quoted rfq becomes expired after validity date', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedProductForManufacturer($manufacturer);

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 1000,
    ])->assertCreated();

    $cancelRfq = RfqSubmission::query()->firstOrFail();

    Passport::actingAs($manufacturer);
    $this->postJson("/api/v1/manufacturer/rfqs/{$cancelRfq->id}/quote", [
        'quoted_price' => 20,
        'quote_currency_code' => 'USD',
        'minimum_order_quantity' => 500,
        'lead_time_days' => 21,
        'quote_valid_until' => now()->addDays(4)->toDateString(),
    ])->assertOk();

    Passport::actingAs($buyer);
    $this->postJson("/api/v1/buyer/rfqs/{$cancelRfq->id}/respond-quote", [
        'action' => 'cancel',
    ])->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 1400,
    ])->assertCreated();

    $expiredRfq = RfqSubmission::query()->latest('id')->firstOrFail();

    Passport::actingAs($manufacturer);
    $this->postJson("/api/v1/manufacturer/rfqs/{$expiredRfq->id}/quote", [
        'quoted_price' => 19,
        'quote_currency_code' => 'USD',
        'minimum_order_quantity' => 800,
        'lead_time_days' => 20,
        'quote_valid_until' => now()->subDay()->toDateString(),
    ])->assertUnprocessable();

    RfqSubmission::query()->whereKey($expiredRfq->id)->update([
        'quoted_price' => 19,
        'quote_currency_code' => 'USD',
        'minimum_order_quantity' => 800,
        'lead_time_days' => 20,
        'quote_valid_until' => now()->subDay()->toDateString(),
        'status' => RfqSubmissionStatus::Quoted->value,
    ]);

    Passport::actingAs($manufacturer);
    $this->getJson('/api/v1/manufacturer/rfqs/counts')->assertOk()
        ->assertJsonPath('data.expired', 1);

    expect($cancelRfq->fresh()->status)->toBe(RfqSubmissionStatus::Cancelled)
        ->and($expiredRfq->fresh()->status)->toBe(RfqSubmissionStatus::Expired);
});
