<?php

use App\Enums\UserRole;
use App\Models\SocialMediaLink;
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

    $this->seed(\Database\Seeders\SocialMediaLinkSeeder::class);
});

function socialMediaAdminToken(): string
{
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    return $admin->createToken('test')->accessToken;
}

test('public can list enabled social media links', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/social-media-links')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data');
});

test('admin can list create update delete and sync social media links', function (): void {
    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.socialMediaAdminToken())
        ->getJson('/api/v1/admin/social-media-links')
        ->assertOk()
        ->assertJsonCount(6, 'data');

    $createResponse = $this->withHeader('Authorization', 'Bearer '.socialMediaAdminToken())
        ->postJson('/api/v1/admin/social-media-links', [
            'platform' => 'Threads',
            'icon' => 'Share2',
            'url' => 'https://threads.net/sourcenest',
            'enabled' => true,
            'sort' => 7,
        ])
        ->assertCreated()
        ->assertJsonPath('data.platform', 'Threads');

    $linkId = $createResponse->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.socialMediaAdminToken())
        ->putJson("/api/v1/admin/social-media-links/{$linkId}", [
            'platform' => 'Threads Updated',
        ])
        ->assertOk()
        ->assertJsonPath('data.platform', 'Threads Updated');

    $this->withHeader('Authorization', 'Bearer '.socialMediaAdminToken())
        ->deleteJson("/api/v1/admin/social-media-links/{$linkId}")
        ->assertOk();

    $this->withHeader('Authorization', 'Bearer '.socialMediaAdminToken())
        ->putJson('/api/v1/admin/social-media-links/sync', [
            'links' => [
                [
                    'id' => SocialMediaLink::query()->where('platform', 'Instagram')->value('id'),
                    'platform' => 'Instagram',
                    'icon' => 'Instagram',
                    'url' => 'https://www.instagram.com/sourcenest1/',
                    'enabled' => true,
                    'sort' => 1,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(6, 'data');

    expect(SocialMediaLink::query()->count())->toBe(6);
    expect(SocialMediaLink::query()->where('platform', 'Instagram')->value('url'))
        ->toBe('https://www.instagram.com/sourcenest1/');
});
