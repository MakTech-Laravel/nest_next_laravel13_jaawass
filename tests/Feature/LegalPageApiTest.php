<?php

use App\Enums\UserRole;
use App\Models\LegalPage;
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

    $this->seed(\Database\Seeders\LegalPageSeeder::class);
});

function legalPageAdminToken(): string
{
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    return $admin->createToken('test')->accessToken;
}

test('public can list enabled legal pages', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/legal-pages')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

test('public can fetch legal page by slug with locale header', function (): void {
    /** @var TestCase $this */
    $this->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/legal-pages/privacy')
        ->assertOk()
        ->assertJsonPath('data.slug', 'privacy')
        ->assertJsonPath('data.title', 'Privacy Policy')
        ->assertJsonCount(5, 'data.sections');
});

test('public returns not found for disabled legal page', function (): void {
    LegalPage::query()->where('slug', 'privacy')->update(['enabled' => false]);

    /** @var TestCase $this */
    $this->getJson('/api/v1/legal-pages/privacy')
        ->assertNotFound();
});

test('admin can list and update legal page content', function (): void {
    $page = LegalPage::query()->where('slug', 'terms')->firstOrFail();

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.legalPageAdminToken())
        ->getJson('/api/v1/admin/legal-pages')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->withHeader('Authorization', 'Bearer '.legalPageAdminToken())
        ->putJson("/api/v1/admin/legal-pages/{$page->id}/content", [
            'locale' => 'en',
            'title' => 'Updated Terms of Service',
            'last_updated' => 'June 2026',
            'enabled' => true,
            'sections' => [
                [
                    'section_key' => 'acceptance',
                    'title' => '1. Acceptance of Terms',
                    'content' => 'Updated acceptance content.',
                    'sort' => 1,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Terms of Service')
        ->assertJsonPath('data.last_updated', 'June 2026')
        ->assertJsonCount(1, 'data.sections');
});
