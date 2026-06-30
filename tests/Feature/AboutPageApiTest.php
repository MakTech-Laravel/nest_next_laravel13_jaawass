<?php

use App\Enums\UserRole;
use App\Models\AboutPage;
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

    $this->seed(\Database\Seeders\AboutPageSeeder::class);
});

function aboutPageAdminToken(): string
{
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    return $admin->createToken('test')->accessToken;
}

test('public can fetch enabled about page', function (): void {
    /** @var TestCase $this */
    $this->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/about-page')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.content.hero.title', 'Making Global Sourcing Work Better')
        ->assertJsonPath('data.content.values.title', 'Our Values');
});

test('public returns not found when about page is disabled', function (): void {
    AboutPage::query()->first()?->update(['enabled' => false]);

    /** @var TestCase $this */
    $this->getJson('/api/v1/about-page')
        ->assertNotFound();
});

test('admin can fetch and update about page content', function (): void {
    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.aboutPageAdminToken())
        ->getJson('/api/v1/admin/about-page')
        ->assertOk()
        ->assertJsonPath('data.content.hero.title', 'Making Global Sourcing Work Better');

    $payload = [
        'locale' => 'en',
        'enabled' => true,
        'content' => [
            'hero' => [
                'title' => 'Updated About Title',
                'subtitle' => 'Updated subtitle',
            ],
            'story' => [
                'title' => 'Our Story',
                'paragraphs' => ['Paragraph one'],
            ],
            'mission' => [
                'title' => 'Mission',
                'description' => 'Mission description',
            ],
            'vision' => [
                'title' => 'Vision',
                'description' => 'Vision description',
            ],
            'values' => [
                'title' => 'Values',
                'subtitle' => 'Values subtitle',
                'items' => [
                    [
                        'id' => 'trust',
                        'icon' => 'Shield',
                        'title' => 'Trust',
                        'description' => 'Trust description',
                        'enabled' => true,
                    ],
                ],
            ],
            'why_different' => [
                'title' => 'Why Different',
                'points' => [
                    [
                        'id' => 'reviewed',
                        'title' => 'Reviewed',
                        'description' => 'Reviewed description',
                        'enabled' => true,
                    ],
                ],
            ],
            'cta' => [
                'title' => 'CTA Title',
                'subtitle' => 'CTA subtitle',
                'buyer_button_text' => 'Join Buyer',
                'manufacturer_button_text' => 'Join Manufacturer',
            ],
        ],
    ];

    $this->withHeader('Authorization', 'Bearer '.aboutPageAdminToken())
        ->putJson('/api/v1/admin/about-page', $payload)
        ->assertOk()
        ->assertJsonPath('data.content.hero.title', 'Updated About Title');

    $this->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/about-page')
        ->assertOk()
        ->assertJsonPath('data.content.hero.title', 'Updated About Title');
});
