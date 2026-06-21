<?php

use App\Models\Company;
use App\Models\ManufacturerExportMarket;
use App\Models\ManufacturerExportMarketCountry;
use App\Models\Product;
use App\Support\ExportMarkets\ManufacturerExportMarketVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('suppliers index filters by viewer country code using export markets', function (): void {
    $usSupplier = seedPublicSupplier([
        'slug' => 'us-export-supplier',
        'company_name' => 'US Export Supplier',
        'country' => 'China',
        'export_markets' => [],
    ]);

    ManufacturerExportMarket::query()->create([
        'user_id' => $usSupplier->id,
        'region' => 'North America',
    ])->countries()->createMany([
        ['country_code' => 'US', 'country_name' => 'United States'],
    ]);

    $deSupplier = seedPublicSupplier([
        'slug' => 'de-export-supplier',
        'company_name' => 'DE Export Supplier',
        'country' => 'China',
        'export_markets' => [],
    ]);

    ManufacturerExportMarket::query()->create([
        'user_id' => $deSupplier->id,
        'region' => 'Western Europe',
    ])->countries()->createMany([
        ['country_code' => 'DE', 'country_name' => 'Germany'],
    ]);

    $this->getJson('/api/v1/suppliers?country=US')
        ->assertOk()
        ->assertJsonPath('data.0.id', $usSupplier->id);

    $deResponse = $this->getJson('/api/v1/suppliers?country=DE');

    $deResponse->assertOk();

    expect(collect($deResponse->json('data'))->pluck('id'))
        ->toContain($deSupplier->id)
        ->not->toContain($usSupplier->id);
});

test('products index respects export market visibility for viewer country', function (): void {
    $supplier = seedPublicSupplier([
        'slug' => 'product-export-supplier',
        'export_markets' => [],
    ]);

    ManufacturerExportMarket::query()->create([
        'user_id' => $supplier->id,
        'region' => 'North America',
    ])->countries()->create([
        'country_code' => 'US',
        'country_name' => 'United States',
    ]);

    $product = Product::factory()->create([
        'user_id' => $supplier->id,
        'status' => 'active',
        'is_approved' => true,
    ]);

    $this->getJson('/api/v1/products?country=US')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->id);

    $this->getJson('/api/v1/products?country=DE')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('supplier without export markets uses deterministic default country', function (): void {
    $supplier = seedPublicSupplier([
        'slug' => 'default-export-supplier',
        'export_markets' => [],
    ]);

    $defaultCode = ManufacturerExportMarketVisibility::DEFAULT_COUNTRY_POOL[
        $supplier->id % count(ManufacturerExportMarketVisibility::DEFAULT_COUNTRY_POOL)
    ];

    $visible = app(ManufacturerExportMarketVisibility::class)
        ->supplierVisibleToCountry((int) $supplier->id, $defaultCode);

    expect($visible)->toBeTrue();
});

test('supplier map includes export supplier counts aligned with country codes', function (): void {
    $supplier = seedPublicSupplier([
        'slug' => 'map-export-supplier',
        'country' => 'China',
        'export_markets' => [],
    ]);

    ManufacturerExportMarket::query()->create([
        'user_id' => $supplier->id,
        'region' => 'North America',
    ])->countries()->create([
        'country_code' => 'US',
        'country_name' => 'United States',
    ]);

    $response = $this->getJson('/api/v1/suppliers/map?search=United States&per_page=10');

    $response->assertOk();

    $country = collect($response->json('data.countries'))->firstWhere('country_code', 'US');

    expect($country)->not->toBeNull()
        ->and($country['export_suppliers_count'])->toBeGreaterThan(0)
        ->and($country['has_export_suppliers'])->toBeTrue();
});

test('legacy export market regions remain compatible with country map browsing', function (): void {
    $supplier = seedPublicSupplier([
        'slug' => 'legacy-export-supplier',
        'export_markets' => ['North America', 'Europe'],
    ]);

    $this->getJson('/api/v1/suppliers?country=US')
        ->assertOk()
        ->assertJsonPath('data.0.id', $supplier->id);

    $this->getJson('/api/v1/suppliers?country=DE')
        ->assertOk()
        ->assertJsonPath('data.0.id', $supplier->id);
});
