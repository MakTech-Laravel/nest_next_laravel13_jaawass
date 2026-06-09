<?php

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('admin rejecting manufacturer sets user status to suspended', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $manufacturer = User::factory()->manufacturer()->create([
        'manufacture_status' => UserManuFactureStatus::PENDING,
        'manufacture_status_at' => now(),
        'status' => UserStatus::PENDING,
    ]);

    $token = $admin->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson("/api/v1/admin/users/{$manufacturer->id}/manufacture-status", [
            'status' => UserManuFactureStatus::REJECTED->value,
            'reason' => 'Documents incomplete.',
        ])
        ->assertOk();

    expect($manufacturer->fresh()->status)->toBe(UserStatus::SUSPENDED);
});

test('suspended buyer cannot log in with password', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
        'status' => UserStatus::SUSPENDED,
    ]);

    /** @var TestCase $this */
    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => UserRole::BUYER->value,
        'device_name' => 'tests',
    ])
        ->assertForbidden()
        ->assertJsonPath('message', __('account.suspended'));
});

test('admin cannot unsuspend manufacturer while manufacture is rejected', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $manufacturer = User::factory()->manufacturer()->create([
        'manufacture_status' => UserManuFactureStatus::REJECTED,
        'manufacture_status_reason' => 'Bad docs',
        'manufacture_status_at' => now(),
        'status' => UserStatus::SUSPENDED,
    ]);

    $token = $admin->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson("/api/v1/admin/users/{$manufacturer->id}/unsuspend")
        ->assertUnprocessable()
        ->assertJsonPath('message', __('messages.users.cannot_unsuspend_manufacturer_rejected'));
});

test('admin can unsuspend approved manufacturer that was manually suspended', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $manufacturer = User::factory()->manufacturerApproved()->create([
        'status' => UserStatus::SUSPENDED,
    ]);

    $token = $admin->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson("/api/v1/admin/users/{$manufacturer->id}/unsuspend")
        ->assertOk();

    expect($manufacturer->fresh()->status)->toBe(UserStatus::ACTIVE);
});

test('admin suspend revokes passport tokens for target user', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $buyer = User::factory()->create([
        'role' => UserRole::BUYER->value,
        'status' => UserStatus::ACTIVE,
    ]);
    $buyer->createToken('device')->accessToken;

    expect($buyer->tokens()->count())->toBe(1);

    $token = $admin->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson("/api/v1/admin/users/{$buyer->id}/suspend")
        ->assertOk();

    expect($buyer->fresh()->tokens()->where('revoked', false)->count())->toBe(0);
});
