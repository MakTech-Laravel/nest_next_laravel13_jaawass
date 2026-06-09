<?php

use App\Enums\UserRole;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FaqTranslation;
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

test('faq update upserts translations for same locale', function () {
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    $this->seed(LanguageSeeder::class);
    Language::clearCache();

    // Fake the translation orchestrator so tests don't call the external Google API.
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

            // Ensure the source locale row exists so look-ups are consistent.
            $translatedByLocale[$resolvedSource] = $sourceData;

            $model->upsertTranslations($translatedByLocale);
        }
    });

    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $token = $admin->createToken('test')->accessToken;

    $category = FaqCategory::create([
        'name' => 'General',
        'slug' => 'general-'.uniqid(),
        'sort' => 1,
    ]);

    $faq = Faq::create([
        'question' => 'Old Q',
        'answer' => 'Old A',
        'faq_category_id' => $category->id,
        'sort' => 1,
    ]);

    $payload1 = [
        'question' => 'New Q 1',
        'answer' => 'New A 1',
        'faq_category_id' => $category->id,
        'sort' => 1,
        'locale' => 'ar',
    ];

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/faqs/{$faq->id}", $payload1)
        ->assertOk();

    expect(
        FaqTranslation::query()
            ->where('faq_id', $faq->id)
            ->where('locale', 'ar')
            ->count()
    )->toBe(1);

    $payload2 = [
        'question' => 'New Q 2',
        'answer' => 'New A 2',
        'faq_category_id' => $category->id,
        'sort' => 1,
        'locale' => 'ar',
    ];

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/faqs/{$faq->id}", $payload2)
        ->assertOk();

    $countAfter = FaqTranslation::query()
        ->where('faq_id', $faq->id)
        ->where('locale', 'ar')
        ->count();
    expect($countAfter)->toBe(1);

    $updated = FaqTranslation::query()
        ->where('faq_id', $faq->id)
        ->where('locale', 'ar')
        ->first();

    expect($updated)->not->toBeNull();
    expect($updated->question)->toBe('New Q 2');
    expect($updated->answer)->toBe('New A 2');
});
