<?php

use App\Jobs\TranslateModelJob;
use App\Models\Currency;
use App\Models\Industry;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Translation\TranslationOrchestrator;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function createTranslatableProduct(array $overrides = []): Product
{
    $user = User::factory()->create();
    $currency = Currency::query()->firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'sort_order' => 1,
        ]
    );
    $industry = Industry::query()->create([
        'name' => 'Test Industry',
        'slug' => 'test-industry',
    ]);
    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Test Sub Category',
        'slug' => 'test-sub-category',
    ]);

    return Product::query()->create(array_merge([
        'user_id' => $user->id,
        'currency_id' => $currency->id,
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Base Name',
        'description' => 'Base Description',
        'slug' => 'base-name-'.uniqid(),
        'status' => 'active',
    ], $overrides));
}

test('product translation upsert keeps untouched fields when only name is updated', function () {
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    $this->seed(LanguageSeeder::class);
    Language::clearCache();

    $product = createTranslatableProduct([
        'name' => 'Original Name',
        'description' => 'Original Description',
    ]);

    ProductTranslation::query()->create([
        'product_id' => $product->id,
        'locale' => 'es',
        'name' => 'Nombre Inicial',
        'description' => 'Descripcion Inicial',
    ]);

    // Simulate title-only update behavior by passing only changed field.
    app(TranslationOrchestrator::class)->handle(
        $product,
        ['name' => 'Updated Name'],
        'en'
    );

    $es = ProductTranslation::query()
        ->where('product_id', $product->id)
        ->where('locale', 'es')
        ->first();

    $en = ProductTranslation::query()
        ->where('product_id', $product->id)
        ->where('locale', 'en')
        ->first();

    expect($es)->not->toBeNull();
    expect($es->description)->toBe('Descripcion Inicial');
    expect($en)->not->toBeNull();
    expect($en->name)->toBe('Updated Name');
});

test('translation job captures model snapshot version at dispatch time', function () {
    Queue::fake();
    config()->set('translation.queue.enabled', true);

    $product = createTranslatableProduct([
        'name' => 'Snapshot Product',
        'description' => 'Snapshot Description',
        'slug' => 'snapshot-product-'.uniqid(),
    ]);

    $product->autoTranslate(['name' => 'Changed Name'], 'en');

    Queue::assertPushed(TranslateModelJob::class, function (TranslateModelJob $job) use ($product): bool {
        return $job->model->is($product)
            && $job->modelUpdatedAtSnapshot !== null
            && $job->modelUpdatedAtSnapshot === $product->updated_at?->toIso8601String();
    });
});

test('orchestrator skips stale job snapshot and keeps latest translation state', function () {
    config()->set('translation.cache.enabled', false);

    $product = createTranslatableProduct([
        'name' => 'Current Name',
        'description' => 'Current Description',
        'slug' => 'current-name-'.uniqid(),
    ]);

    ProductTranslation::query()->create([
        'product_id' => $product->id,
        'locale' => 'es',
        'name' => 'Nombre Vigente',
        'description' => 'Descripcion Vigente',
    ]);

    $staleSnapshot = $product->updated_at?->toIso8601String();

    $product->update([
        'name' => 'Newer Product Name',
    ]);

    app(TranslationOrchestrator::class)->handle(
        model: $product,
        sourceData: ['name' => 'Stale Job Name'],
        sourceLocale: 'en',
        modelUpdatedAtSnapshot: $staleSnapshot
    );

    $es = ProductTranslation::query()
        ->where('product_id', $product->id)
        ->where('locale', 'es')
        ->first();

    expect($es)->not->toBeNull();
    expect($es->name)->toBe('Nombre Vigente');
    expect($es->description)->toBe('Descripcion Vigente');
});

