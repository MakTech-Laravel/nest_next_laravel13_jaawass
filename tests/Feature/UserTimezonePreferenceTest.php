<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('authenticated user can set timezone preference via me/preferences', function () {
    /** @var User&Authenticatable $user */
    $user = User::factory()->create(['timezone' => 'UTC']);

    $user->timestamps = false;
    $user->forceFill([
        'created_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
        'updated_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
    ])->save();
    Passport::actingAs($user);

    /** @var TestCase $this */
    $response = $this->patchJson('/api/v1/me/preferences', [
        'timezone' => 'Asia/Dhaka',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.timezone', 'Asia/Dhaka')
        ->assertJsonPath('data.created_at', '2020-01-01 06:00:00')
        ->assertJsonPath('data.updated_at', '2020-01-01 06:00:00');

    expect($user->fresh()->timezone)->toBe('Asia/Dhaka');
});

test('X-App-Timezone header overrides user saved timezone on protected endpoints', function () {
    /** @var User&Authenticatable $user */
    $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

    $user->timestamps = false;
    $user->forceFill([
        'created_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
        'updated_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
    ])->save();

    Passport::actingAs($user);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/me', [
        'X-App-Timezone' => 'UTC',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.created_at', '2020-01-01 00:00:00')
        ->assertJsonPath('data.updated_at', '2020-01-01 00:00:00');
});

test('Accept-Timezone header overrides user saved timezone when X-App-Timezone is absent', function () {
    /** @var User&Authenticatable $user */
    $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

    $user->timestamps = false;
    $user->forceFill([
        'created_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
        'updated_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
    ])->save();

    Passport::actingAs($user);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/me', [
        'Accept-Timezone' => 'UTC',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.created_at', '2020-01-01 00:00:00')
        ->assertJsonPath('data.updated_at', '2020-01-01 00:00:00');
});

test('different timezone headers return different formatted timestamps', function () {
    /** @var User&Authenticatable $user */
    $user = User::factory()->create(['timezone' => 'UTC']);

    $user->timestamps = false;
    $user->forceFill([
        'created_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
        'updated_at' => CarbonImmutable::parse('2020-01-01 00:00:00', 'UTC'),
    ])->save();

    Passport::actingAs($user);

    /** @var TestCase $this */
    $dhaka = $this->getJson('/api/v1/me', [
        'X-App-Timezone' => 'Asia/Dhaka',
    ]);
    $dhaka->assertOk()
        ->assertJsonPath('data.created_at', '2020-01-01 06:00:00');

    // Scoped instances may persist between requests within one test.
    app()->forgetScopedInstances();

    /** @var TestCase $this */
    $karachi = $this->getJson('/api/v1/me', [
        'X-App-Timezone' => 'Asia/Karachi',
    ]);
    $karachi->assertOk()
        ->assertJsonPath('data.created_at', '2020-01-01 05:00:00');
});

test('timezone preference must be a valid timezone identifier', function () {
    /** @var User&Authenticatable $user */
    $user = User::factory()->create();
    Passport::actingAs($user);

    /** @var TestCase $this */
    $response = $this->patchJson('/api/v1/me/preferences', [
        'timezone' => 'Not/A_Timezone',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['timezone']);
});
