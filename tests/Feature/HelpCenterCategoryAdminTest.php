<?php

use App\Enums\UserRole;
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

function helpCenterAdminToken(): string
{
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);

    return $admin->createToken('test')->accessToken;
}

test('admin can create help center category and shift sort order', function (): void {
    HelpCenterCategory::query()->create([
        'name' => 'First',
        'slug' => 'first',
        'sort_order' => 1,
    ]);
    HelpCenterCategory::query()->create([
        'name' => 'Second',
        'slug' => 'second',
        'sort_order' => 2,
    ]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.helpCenterAdminToken())
        ->postJson('/api/v1/admin/help-center/categories/create', [
            'name' => 'Inserted',
            'slug' => 'inserted',
            'sort_order' => 1,
        ])
        ->assertCreated()
        ->assertJsonPath('data.sort_order', 1);

    expect(HelpCenterCategory::query()->where('slug', 'first')->value('sort_order'))->toBe(2);
    expect(HelpCenterCategory::query()->where('slug', 'second')->value('sort_order'))->toBe(3);
});

test('admin can update help center category position', function (): void {
    $a = HelpCenterCategory::query()->create(['name' => 'A', 'slug' => 'a', 'sort_order' => 1]);
    HelpCenterCategory::query()->create(['name' => 'B', 'slug' => 'b', 'sort_order' => 2]);
    $c = HelpCenterCategory::query()->create(['name' => 'C', 'slug' => 'c', 'sort_order' => 3]);

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.helpCenterAdminToken())
        ->putJson("/api/v1/admin/help-center/categories/{$c->id}/position", [
            'new_position' => 1,
        ])
        ->assertOk()
        ->assertJsonPath('data.sort_order', 1);

    expect(HelpCenterCategory::query()->where('slug', 'a')->value('sort_order'))->toBe(2);
    expect(HelpCenterCategory::query()->where('slug', 'b')->value('sort_order'))->toBe(3);
    expect(HelpCenterCategory::query()->where('slug', 'c')->value('sort_order'))->toBe(1);
});

test('admin can list update and delete help center categories', function (): void {
    $category = HelpCenterCategory::query()->create([
        'name' => 'Billing',
        'slug' => 'billing',
        'sort_order' => 1,
    ]);

    $token = helpCenterAdminToken();

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/help-center/categories')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'billing');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson("/api/v1/admin/help-center/categories/{$category->id}", [
            'name' => 'Billing Updated',
            'slug' => 'billing-updated',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Billing Updated');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/v1/admin/help-center/categories/{$category->id}")
        ->assertOk();

    expect(HelpCenterCategory::query()->find($category->id))->toBeNull();
});
