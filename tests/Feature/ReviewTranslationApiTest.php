<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Models\Language;
use App\Models\Review;
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
});

function fakeReviewTranslationOrchestrator(): void
{
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    app()->instance(TranslationOrchestrator::class, new class
    {
        public function handle(Model $model, array $sourceData, ?string $sourceLocale = null): void
        {
            $translatableFields = $model->translatableFields();
            $sourceData = array_intersect_key($sourceData, array_flip($translatableFields));

            if ($sourceData === []) {
                return;
            }

            $resolvedSource = $sourceLocale ?? config('translation.source_locale', 'en');
            $targets = Language::translationTargets($resolvedSource);

            $translatedByLocale = [];

            foreach ($targets as $language) {
                $translated = [];

                foreach ($sourceData as $fieldKey => $sourceText) {
                    $translated[$fieldKey] = $language->locale.'_'.$sourceText;
                }

                $translatedByLocale[$language->locale] = $translated;
            }

            $translatedByLocale[$resolvedSource] = $sourceData;

            $model->upsertTranslations($translatedByLocale);
        }
    });
}

test('buyer review submission stores and translates review content', function (): void {
    $this->seed(LanguageSeeder::class);
    Language::clearCache();
    fakeReviewTranslationOrchestrator();

    ['buyer' => $buyer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Exceptional quality and communication',
        'comment' => 'Consistently high quality and responsive team.',
        'locale' => 'en',
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Exceptional quality and communication')
        ->assertJsonPath('data.comment', 'Consistently high quality and responsive team.');

    $review = Review::query()->firstOrFail();

    expect($review->translations()->count())->toBeGreaterThan(1);
});

test('admin review endpoints return localized content and all translations', function (): void {
    $this->seed(LanguageSeeder::class);
    Language::clearCache();
    fakeReviewTranslationOrchestrator();

    ['buyer' => $buyer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Perfect for sustainable brands',
        'comment' => 'EcoThread has been our go-to supplier for organic cotton products.',
        'locale' => 'en',
    ])->assertCreated();

    $review = Review::query()->firstOrFail();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($admin);

    $this->getJson("/api/v1/admin/reviews/{$review->id}?locale=ar")
        ->assertOk()
        ->assertJsonPath('data.title', 'ar_Perfect for sustainable brands')
        ->assertJsonPath('data.comment', 'ar_EcoThread has been our go-to supplier for organic cotton products.')
        ->assertJsonStructure([
            'data' => [
                'available_locales',
                'translations' => [
                    ['locale', 'title', 'comment'],
                ],
            ],
        ]);

    $this->patchJson("/api/v1/admin/reviews/{$review->id}", [
        'title' => 'Updated review title',
        'comment' => 'Updated review comment for moderation.',
        'locale' => 'en',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated review title');

    expect($review->fresh()->title)->toBe('Updated review title');
});

test('admin review responses translate static messages and labels for requested locale', function (): void {
    $this->seed(LanguageSeeder::class);
    Language::clearCache();
    fakeReviewTranslationOrchestrator();

    ['buyer' => $buyer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Perfect for sustainable brands',
        'comment' => 'EcoThread has been our go-to supplier for organic cotton products.',
        'locale' => 'en',
    ])->assertCreated();

    $review = Review::query()->firstOrFail();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($admin);

    $this->getJson('/api/v1/admin/reviews/stats?locale=ar', [
        'Accept-Language' => 'ar',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'تم جلب إحصائيات المراجعات بنجاح.')
        ->assertJsonPath('data.labels.total_reviews', 'إجمالي المراجعات')
        ->assertJsonPath('data.status_options.0.label', 'قيد المراجعة');

    $this->getJson("/api/v1/admin/reviews/{$review->id}?locale=ar", [
        'Accept-Language' => 'ar',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'تم جلب المراجعة بنجاح.')
        ->assertJsonPath('data.status_label', 'قيد المراجعة')
        ->assertJsonCount(4, 'data.translations');
});

test('public product reviews return translated content for requested locale', function (): void {
    $this->seed(LanguageSeeder::class);
    Language::clearCache();
    fakeReviewTranslationOrchestrator();

    ['buyer' => $buyer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Exceptional quality and communication',
        'comment' => 'Consistently high quality and responsive team.',
        'locale' => 'en',
    ])->assertCreated();

    Review::query()->first()?->update(['status' => ReviewStatus::PUBLISHED->value]);

    auth('api')->forgetUser();

    $this->getJson("/api/v1/products/{$productId}?locale=ar")
        ->assertOk()
        ->assertJsonPath('data.reviews.0.title', 'ar_Exceptional quality and communication')
        ->assertJsonPath('data.reviews.0.comment', 'ar_Consistently high quality and responsive team.');
});
