<?php

use App\Enums\UserRole;
use App\Models\FaqCategory;
use App\Models\FaqCategoryTranslation;
use App\Models\Language;
use App\Models\User;
use App\Services\Translation\TranslationOrchestrator;
use Database\Seeders\LanguageSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('faq category update upserts translations for same locale', function () {
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    $this->seed(LanguageSeeder::class);
    Language::clearCache();

    app()->instance(TranslationOrchestrator::class, new class
    {
        public function handle(Model $model, array $sourceData, ?string $sourceLocale = null): void
        {
            $translatableFields = $model->translatableFields();
            $sourceData = array_intersect_key($sourceData, array_flip($translatableFields));

            if (empty($sourceData)) {
                return;
            }

            $resolvedSource = $sourceLocale ?? config('translation.source_locale', 'en');
            $targets = Language::translationTargets($resolvedSource);

            $translatedByLocale = [];

            foreach ($targets as $language) {
                $translated = [];

                foreach ($sourceData as $fieldKey => $_sourceText) {
                    $translated[$fieldKey] = $language->locale.'_'.$fieldKey.'_t';
                }

                $translatedByLocale[$language->locale] = $translated;
            }

            $translatedByLocale[$resolvedSource] = $sourceData;
            $model->upsertTranslations($translatedByLocale);
        }
    });

    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $token = $admin->createToken('test')->accessToken;

    $category = FaqCategory::create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'sort' => 1,
    ]);

    $payload1 = [
        'name' => 'Updated Name 1',
        'slug' => 'old-name',
        'locale' => 'ar',
    ];

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/faqs/categories/{$category->id}", $payload1)
        ->assertOk();

    expect(
        FaqCategoryTranslation::query()
            ->where('faq_category_id', $category->id)
            ->where('locale', 'ar')
            ->count()
    )->toBe(1);

    $payload2 = [
        'name' => 'Updated Name 2',
        'slug' => 'old-name',
        'locale' => 'ar',
    ];

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/faqs/categories/{$category->id}", $payload2)
        ->assertOk();

    $countAfter = FaqCategoryTranslation::query()
        ->where('faq_category_id', $category->id)
        ->where('locale', 'ar')
        ->count();
    expect($countAfter)->toBe(1);

    $updated = FaqCategoryTranslation::query()
        ->where('faq_category_id', $category->id)
        ->where('locale', 'ar')
        ->first();

    expect($updated)->not->toBeNull();
    expect($updated->name)->toBe('Updated Name 2');
});

