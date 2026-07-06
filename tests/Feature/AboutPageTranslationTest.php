<?php

use App\Enums\UserRole;
use App\Jobs\TranslateAboutPageContentJob;
use App\Models\User;
use App\Support\Cms\AboutPageContentFlattener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    $this->seed(\Database\Seeders\AboutPageSeeder::class);
});

function aboutPageTranslationAdminToken(): string
{
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    return $admin->createToken('test')->accessToken;
}

test('about page content flattener preserves structure', function (): void {
    $content = [
        'hero' => [
            'title' => 'Hello',
            'subtitle' => 'World',
        ],
        'story' => [
            'title' => 'Story',
            'paragraphs' => ['One', 'Two'],
        ],
    ];

    $flat = AboutPageContentFlattener::flatten($content);

    expect($flat)->toMatchArray([
        'hero.title' => 'Hello',
        'hero.subtitle' => 'World',
        'story.title' => 'Story',
        'story.paragraphs.0' => 'One',
        'story.paragraphs.1' => 'Two',
    ]);

    $translated = AboutPageContentFlattener::apply($content, [
        'hero.title' => 'Hola',
        'story.paragraphs.1' => 'Dos',
    ]);

    expect($translated['hero']['title'])->toBe('Hola')
        ->and($translated['story']['paragraphs'][1])->toBe('Dos')
        ->and($translated['hero']['subtitle'])->toBe('World');
});

test('admin about page update dispatches translation job', function (): void {
    Queue::fake();

    $token = aboutPageTranslationAdminToken();

    $payload = [
        'locale' => 'en',
        'enabled' => true,
        'content' => [
            'hero' => ['title' => 'Updated About Title', 'subtitle' => 'Updated subtitle'],
            'story' => ['title' => 'Our Story', 'paragraphs' => ['Paragraph one']],
            'mission' => ['title' => 'Mission', 'description' => 'Mission description'],
            'vision' => ['title' => 'Vision', 'description' => 'Vision description'],
            'values' => [
                'title' => 'Values',
                'subtitle' => 'Values subtitle',
                'items' => [[
                    'id' => 'trust',
                    'icon' => 'Shield',
                    'title' => 'Trust',
                    'description' => 'Trust description',
                    'enabled' => true,
                ]],
            ],
            'why_different' => [
                'title' => 'Why Different',
                'points' => [[
                    'id' => 'reviewed',
                    'title' => 'Reviewed',
                    'description' => 'Reviewed description',
                    'enabled' => true,
                ]],
            ],
            'cta' => [
                'title' => 'CTA Title',
                'subtitle' => 'CTA subtitle',
                'buyer_button_text' => 'Join Buyer',
                'manufacturer_button_text' => 'Join Manufacturer',
            ],
        ],
    ];

    test()->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/admin/about-page', $payload)
        ->assertOk();

    Queue::assertPushed(TranslateAboutPageContentJob::class);
});
