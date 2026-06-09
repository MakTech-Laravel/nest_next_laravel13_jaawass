<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('admin can create industry with new palette fields', function (): void {
    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $payload = [
        'name' => 'Electronics',
        'slug' => 'electronics',
        'color' => '#111111',
        'title_color' => '#000000',
        'desc_color' => '#64748b',
        'btn_color' => '#3b82f6',
        'supplier_color' => '#64748b',
        'description' => 'Industry description',
        'featured' => true,
    ];

    $response = $this->postJson('/api/v1/admin/categories/create', $payload);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title_color', '#000000')
        ->assertJsonPath('data.desc_color', '#64748b')
        ->assertJsonPath('data.btn_color', '#3b82f6')
        ->assertJsonPath('data.supplier_color', '#64748b');

    $this->assertDatabaseHas('industries', [
        'name' => 'Electronics',
        'slug' => 'electronics',
        'title_color' => '#000000',
        'desc_color' => '#64748b',
        'btn_color' => '#3b82f6',
        'supplier_color' => '#64748b',
    ]);
});

test('industry icon in response is raw path while icon_url is absolute', function (): void {
    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $payload = [
        'name' => 'Mining',
        'slug' => 'mining',
        'icon' => 'industries/mining.png',
        'description' => null,
        'featured' => false,
    ];

    $response = $this->postJson('/api/v1/admin/categories/create', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.icon', 'industries/mining.png')
        ->assertJsonPath('data.icon_url', Storage::disk('public')->url('industries/mining.png'));
});
