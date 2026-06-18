<?php

declare(strict_types=1);

use App\Models\RfqQuoteAttachment;
use App\Models\RfqSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    Storage::fake('public');
});

test('manufacturer can send extended quote with certifications and attachments', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedOrderSelectProduct($manufacturer);

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 50000,
    ])->assertCreated();

    $rfq = RfqSubmission::query()->firstOrFail();

    Passport::actingAs($manufacturer);

    $response = $this->post("/api/v1/manufacturer/rfqs/{$rfq->id}/quote", [
        'quoted_price' => 1.05,
        'quote_currency_code' => 'USD',
        'minimum_order_quantity' => 10000,
        'lead_time_days' => 30,
        'lead_time' => '30 days',
        'quote_valid_until' => now()->addDays(14)->toDateString(),
        'quote_shipping_terms' => 'FOB',
        'quote_payment_terms' => '30-70',
        'sample_cost' => '$50 including shipping',
        'sample_lead_time' => '7 days',
        'quote_packaging_details' => '24 rolls per case, custom branding available',
        'quote_certifications' => ['ISO 9001', 'FSC'],
        'quote_notes' => 'Volume discount available above 100k units.',
        'photos' => [
            UploadedFile::fake()->image('product-front.jpg'),
        ],
        'attachments' => [
            UploadedFile::fake()->create('quotation.pdf', 120, 'application/pdf'),
        ],
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'quoted')
        ->assertJsonPath('data.quoted_price', '1.05')
        ->assertJsonPath('data.quote_shipping_terms', 'FOB')
        ->assertJsonPath('data.quote_payment_terms', '30-70')
        ->assertJsonPath('data.sample_cost', '$50 including shipping')
        ->assertJsonPath('data.sample_lead_time', '7 days')
        ->assertJsonPath('data.quote_packaging_details', '24 rolls per case, custom branding available')
        ->assertJsonPath('data.quote_certifications.0', 'ISO 9001')
        ->assertJsonPath('data.quote_certifications.1', 'FSC')
        ->assertJsonPath('data.quote_notes', 'Volume discount available above 100k units.')
        ->assertJsonCount(1, 'data.quote_photos')
        ->assertJsonCount(1, 'data.quote_documents')
        ->assertJsonCount(2, 'data.quote_attachments');

    expect(RfqQuoteAttachment::query()->count())->toBe(2);

    Passport::actingAs($buyer);
    $this->getJson("/api/v1/buyer/rfqs/{$rfq->id}")
        ->assertOk()
        ->assertJsonPath('data.quote_shipping_terms', 'FOB')
        ->assertJsonCount(2, 'data.quote_attachments');
});
