<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Industry;
use App\Models\Language;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Translation\TranslationOrchestrator;
use Database\Seeders\LanguageSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    $this->seed(LanguageSeeder::class);
    Language::clearCache();

    app()->instance(TranslationOrchestrator::class, new class
    {
        public function handle(Model $model, array $sourceData, ?string $sourceLocale = null, ?string $modelUpdatedAtSnapshot = null): void
        {
            $translatableFields = $model->translatableFields();
            $sourceData = array_intersect_key($sourceData, array_flip($translatableFields));

            if ($sourceData === []) {
                return;
            }

            $resolvedSource = $sourceLocale ?? config('translation.source_locale', 'en');
            $targets = Language::translationTargets($resolvedSource);
            $translatedByLocale = [$resolvedSource => $sourceData];

            foreach ($targets as $language) {
                $translated = [];

                foreach ($sourceData as $fieldKey => $sourceText) {
                    $translated[$fieldKey] = $language->locale.'_'.$sourceText;
                }

                $translatedByLocale[$language->locale] = $translated;
            }

            $model->upsertTranslations($translatedByLocale);
        }
    });
});

test('manufacturer can create product with minimal required fields', function (): void {
    $currency = Currency::query()->where('code', 'USD')->firstOrFail();

    $industry = Industry::query()->create([
        'name' => 'Electronics',
        'description' => null,
        'slug' => 'electronics-'.uniqid(),
        'icon' => null,
        'color' => null,
        'featured' => false,
        'sort_order' => 0,
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Components',
        'slug' => 'components-'.uniqid(),
        'icon' => null,
        'sort_order' => 0,
    ]);

    $manufacturer = User::factory()->manufacturerApproved()->create();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/products', [
        'name' => 'Industrial Sensor',
        'description' => 'High precision industrial sensor.',
        'category_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'min_price' => 10,
        'max_price' => 100,
        'currency_id' => $currency->id,
        'minimum_order_quantity' => 50,
        'unit' => 'pcs',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Industrial Sensor');

    expect(Product::query()->where('user_id', $manufacturer->id)->count())->toBe(1);
});

test('unauthenticated manufacturer product create returns json unauthorized', function (): void {
    $this->postJson('/api/v1/manufacturer/products', [])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', __('api.unauthenticated'));
});
