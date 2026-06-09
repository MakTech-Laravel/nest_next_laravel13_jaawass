<?php

declare(strict_types=1);

use App\Models\Industry;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['translation.queue.enabled' => false]);

    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('admin can create subcategory with description tags and string icon', function (): void {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $industry = Industry::query()->create([
        'name' => 'Machinery',
        'slug' => 'machinery-'.uniqid(),
    ]);

    $payload = [
        'industry_id' => $industry->id,
        'name' => 'Consumer Electronics',
        'slug' => 'consumer-electronics',
        'description' => 'Describe this category.',
        'icon' => 'subcategories/ce.png',
        'tags' => ['electronics', 'gadgets', 'tech'],
        'sort_order' => 2,
    ];

    $response = $this->postJson('/api/v1/admin/subcategories/create', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Consumer Electronics')
        ->assertJsonPath('data.slug', 'consumer-electronics')
        ->assertJsonPath('data.description', 'Describe this category.')
        ->assertJsonPath('data.icon', 'subcategories/ce.png')
        ->assertJsonPath('data.tags', ['electronics', 'gadgets', 'tech'])
        ->assertJsonPath('data.sort_order', 2);

    $this->assertDatabaseHas('sub_categories', [
        'industry_id' => $industry->id,
        'slug' => 'consumer-electronics',
        'description' => 'Describe this category.',
        'icon' => 'subcategories/ce.png',
        'sort_order' => 2,
    ]);
});

test('admin can update subcategory and show returns full payload', function (): void {
    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $industry = Industry::query()->create([
        'name' => 'Industry',
        'slug' => 'industry-'.uniqid(),
    ]);

    $sub = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Old Name',
        'slug' => 'old-name-'.uniqid(),
        'description' => 'Old desc',
        'tags' => ['a'],
        'sort_order' => 0,
    ]);

    $update = $this->putJson("/api/v1/admin/subcategories/{$sub->id}", [
        'industry_id' => $industry->id,
        'name' => 'New Name',
        'slug' => $sub->slug,
        'description' => 'Updated description',
        'icon' => 'https://cdn.example.com/icon.svg',
        'tags' => ['x', 'y'],
        'sort_order' => 5,
    ]);

    $update->assertOk()
        ->assertJsonPath('data.description', 'Updated description')
        ->assertJsonPath('data.icon', 'https://cdn.example.com/icon.svg')
        ->assertJsonPath('data.tags', ['x', 'y']);

    $show = $this->getJson("/api/v1/admin/subcategories/{$sub->id}");
    $show->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.description', 'Updated description');
});

test('admin can delete subcategory', function (): void {
    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $industry = Industry::query()->create([
        'name' => 'Industry',
        'slug' => 'industry-'.uniqid(),
    ]);

    $sub = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'To Delete',
        'slug' => 'to-delete-'.uniqid(),
    ]);

    $response = $this->deleteJson("/api/v1/admin/subcategories/{$sub->id}");

    $response->assertOk();
    expect(SubCategory::query()->find($sub->id))->toBeNull();
});
