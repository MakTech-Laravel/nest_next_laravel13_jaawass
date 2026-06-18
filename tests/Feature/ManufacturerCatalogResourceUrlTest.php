<?php

declare(strict_types=1);

use App\Models\Catalog;
use App\Models\User;
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

test('manufacturer catalog show returns absolute public file URL', function (): void {
    $manufacturer = manufacturerWithSubscription();

    $catalog = Catalog::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Spring Collection',
        'file_path' => 'catalogs/catalog.pdf',
        'file_size' => 1024,
        'status' => 'active',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->get("/api/v1/manufacturer/catalogs/{$catalog->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.file_path', url('/storage/catalogs/catalog.pdf'));
});

test('manufacturer can update catalog metadata without uploading a new file', function (): void {
    $manufacturer = manufacturerWithSubscription();

    $catalog = Catalog::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Old Name',
        'file_path' => 'catalogs/catalog-old.pdf',
        'file_size' => 2048,
        'status' => 'active',
    ]);

    Passport::actingAs($manufacturer);

    $response = $this->putJson("/api/v1/manufacturer/catalogs/{$catalog->id}", [
        'name' => 'Updated Name',
        'status' => 'inactive',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.status', 'inactive');
});
