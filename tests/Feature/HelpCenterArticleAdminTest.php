<?php

use App\Enums\UserRole;
use App\Models\HelpCenterArticle;
use App\Models\HelpCenterCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

function articleAdminToken(): string
{
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    return $admin->createToken('test')->accessToken;
}

test('admin can create article with steps', function (): void {
    $category = HelpCenterCategory::query()->create([
        'name' => 'For Manufacturers',
        'slug' => 'for-manufacturers',
        'sort_order' => 1,
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.articleAdminToken())
        ->postJson('/api/v1/admin/help-center/articles/create', [
            'help_center_category_id' => $category->id,
            'title' => 'Understanding your analytics',
            'description' => 'Use analytics to optimize your presence.',
            'steps' => [
                ['content' => 'Check profile views and visitor demographics', 'sort_order' => 1],
                ['content' => 'Monitor product inquiry rates and popular items', 'sort_order' => 2],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Understanding your analytics')
        ->assertJsonCount(2, 'data.steps');

    expect(HelpCenterArticle::query()->count())->toBe(1);
});

test('admin can search articles by title', function (): void {
    $category = HelpCenterCategory::query()->create([
        'name' => 'Billing',
        'slug' => 'billing',
        'sort_order' => 1,
    ]);

    HelpCenterArticle::query()->create([
        'help_center_category_id' => $category->id,
        'title' => 'Subscription plans explained',
        'description' => 'Plans overview',
        'sort_order' => 1,
    ]);

    HelpCenterArticle::query()->create([
        'help_center_category_id' => $category->id,
        'title' => 'Payment methods',
        'description' => 'Cards and wire',
        'sort_order' => 2,
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.articleAdminToken())
        ->getJson('/api/v1/admin/help-center/articles?search=Subscription')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Subscription plans explained');
});

test('creating article upserts source locale translations and auto translates steps', function (): void {
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    $category = HelpCenterCategory::query()->create([
        'name' => 'For Manufacturers',
        'slug' => 'for-manufacturers',
        'sort_order' => 1,
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.articleAdminToken())
        ->postJson('/api/v1/admin/help-center/articles/create', [
            'help_center_category_id' => $category->id,
            'title' => 'Understanding your analytics',
            'description' => 'Use analytics to optimize your presence.',
            'locale' => 'en',
            'steps' => [
                ['content' => 'Check profile views and visitor demographics'],
            ],
        ])
        ->assertCreated();

    $article = HelpCenterArticle::query()->first();

    expect($article)->not->toBeNull();
    expect($article->translations()->where('locale', 'en')->value('title'))
        ->toBe('Understanding your analytics');

    $step = $article->steps()->first();
    expect($step)->not->toBeNull();
    expect($step->translations()->where('locale', 'en')->value('content'))
        ->toBe('Check profile views and visitor demographics');
});

test('updating article persists model and source locale translations', function (): void {
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    $category = HelpCenterCategory::query()->create([
        'name' => 'Billing',
        'slug' => 'billing',
        'sort_order' => 1,
    ]);

    $article = HelpCenterArticle::query()->create([
        'help_center_category_id' => $category->id,
        'title' => 'Old title',
        'description' => 'Old description',
        'sort_order' => 1,
    ]);

    $article->upsertTranslations([
        'en' => [
            'title' => 'Old title',
            'description' => 'Old description',
        ],
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.articleAdminToken())
        ->putJson("/api/v1/admin/help-center/articles/{$article->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'locale' => 'en',
            'steps' => [
                ['content' => 'Updated step one'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated title')
        ->assertJsonPath('data.description', 'Updated description')
        ->assertJsonPath('data.steps.0.content', 'Updated step one');

    $article->refresh();

    expect($article->title)->toBe('Updated title');
    expect($article->description)->toBe('Updated description');
    expect($article->translations()->where('locale', 'en')->value('title'))->toBe('Updated title');
    expect($article->steps()->first()?->content)->toBe('Updated step one');
});

test('update with locale in body persists translations and returns updated content', function (): void {
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);
    config()->set('translation.source_locale', 'en');

    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
        'preferred_language' => 'ar',
    ]);

    $token = $admin->createToken('test')->accessToken;

    $category = HelpCenterCategory::query()->create([
        'name' => 'Billing',
        'slug' => 'billing',
        'sort_order' => 1,
    ]);

    $article = HelpCenterArticle::query()->create([
        'help_center_category_id' => $category->id,
        'title' => 'Test',
        'description' => 'Test description',
        'sort_order' => 5,
    ]);

    $article->upsertTranslations([
        'en' => ['title' => 'Test', 'description' => 'Test description'],
        'ar' => ['title' => 'Arabic Test', 'description' => 'Arabic description'],
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/help-center/articles/{$article->id}?locale=en", [
            'help_center_category_id' => $category->id,
            'title' => 'testsssss',
            'description' => 'Test description',
            'locale' => 'en',
            'steps' => [
                ['content' => 'Test 2ss'],
                ['content' => 'Test 3'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'testsssss')
        ->assertJsonPath('data.steps.0.content', 'Test 2ss')
        ->assertJsonPath('data.steps.1.content', 'Test 3');

    $article->refresh();

    expect($article->title)->toBe('testsssss');
    expect($article->translations()->where('locale', 'en')->value('title'))->toBe('testsssss');
    expect($article->steps()->count())->toBe(2);
});

test('update uses query locale when locale is not in body', function (): void {
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
        'preferred_language' => 'ar',
    ]);

    $token = $admin->createToken('test')->accessToken;

    $category = HelpCenterCategory::query()->create([
        'name' => 'Billing',
        'slug' => 'billing',
        'sort_order' => 1,
    ]);

    $article = HelpCenterArticle::query()->create([
        'help_center_category_id' => $category->id,
        'title' => 'Test',
        'description' => 'Old',
        'sort_order' => 1,
    ]);

    $article->upsertTranslations([
        'en' => ['title' => 'Test', 'description' => 'Old'],
        'ar' => ['title' => 'Arabic', 'description' => 'Arabic old'],
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/help-center/articles/{$article->id}?locale=en", [
            'title' => 'Header locale title',
            'description' => 'Header locale description',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Header locale title');

    expect($article->fresh()->translations()->where('locale', 'en')->value('title'))
        ->toBe('Header locale title');
});

test('show returns not found for missing article', function (): void {
    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.articleAdminToken())
        ->getJson('/api/v1/admin/help-center/articles/99999')
        ->assertNotFound()
        ->assertJsonPath('message', __('common.not_found'));
});

test('admin can filter articles by category', function (): void {
    $buyers = HelpCenterCategory::query()->create(['name' => 'Buyers', 'slug' => 'buyers', 'sort_order' => 1]);
    $billing = HelpCenterCategory::query()->create(['name' => 'Billing', 'slug' => 'billing', 'sort_order' => 2]);

    HelpCenterArticle::query()->create([
        'help_center_category_id' => $buyers->id,
        'title' => 'Buyer article',
        'sort_order' => 1,
    ]);

    HelpCenterArticle::query()->create([
        'help_center_category_id' => $billing->id,
        'title' => 'Billing article',
        'sort_order' => 1,
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.articleAdminToken())
        ->getJson("/api/v1/admin/help-center/articles?help_center_category_id={$billing->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Billing article');
});
