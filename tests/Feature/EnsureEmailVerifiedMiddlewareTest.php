<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\PlatformSetting;
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

function setRequireEmailVerification(bool $required): void
{
    PlatformSetting::query()->updateOrCreate(
        ['group' => 'security'],
        ['payload' => ['require_email_verification' => $required]],
    );
}

test('buyer routes are blocked when email verification is required and email is unverified', function (): void {
    setRequireEmailVerification(true);

    $buyer = User::factory()->unverified()->create(['role' => UserRole::BUYER->value]);
    Passport::actingAs($buyer);

    $this->getJson('/api/v1/buyer/orders')
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('data.code', 'email_not_verified');
});

test('manufacturer routes are blocked when email verification is required and email is unverified', function (): void {
    setRequireEmailVerification(true);

    $manufacturer = User::factory()->unverified()->create(['role' => UserRole::MANUFACTURER->value]);
    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/review-center')
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('data.code', 'email_not_verified');
});

test('buyer and manufacturer routes allow access when email is verified', function (): void {
    setRequireEmailVerification(true);

    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    Passport::actingAs($buyer);

    $this->getJson('/api/v1/buyer/orders')->assertOk();

    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/review-center')->assertOk();
});

test('buyer and manufacturer routes allow unverified users when email verification is disabled', function (): void {
    setRequireEmailVerification(false);

    $buyer = User::factory()->unverified()->create(['role' => UserRole::BUYER->value]);
    Passport::actingAs($buyer);

    $this->getJson('/api/v1/buyer/orders')->assertOk();

    $manufacturer = User::factory()->unverified()->create(['role' => UserRole::MANUFACTURER->value]);
    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/review-center')->assertOk();
});

test('common routes remain accessible for unverified users', function (): void {
    setRequireEmailVerification(true);

    $buyer = User::factory()->unverified()->create(['role' => UserRole::BUYER->value]);
    Passport::actingAs($buyer);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.is_email_verified', false);
});

test('email verification error message is localized via Accept-Language', function (): void {
    setRequireEmailVerification(true);

    $buyer = User::factory()->unverified()->create(['role' => UserRole::BUYER->value]);
    Passport::actingAs($buyer);

    $this->getJson('/api/v1/buyer/orders', [
        'Accept-Language' => 'ar',
    ])
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('data.code', 'email_not_verified')
        ->assertJsonPath('message', __('api.email_verification_required', [], 'ar'));
});
