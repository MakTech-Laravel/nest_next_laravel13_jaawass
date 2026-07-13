<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
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

test('login error message uses Accept-Language zh_CN when supported', function () {
    User::factory()->create([
        'email' => 'u@example.com',
        'password' => 'secret123',
        'role' => UserRole::BUYER->value,
    ]);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/login', [
        'email' => 'u@example.com',
        'password' => 'wrong-password',
        'role' => UserRole::BUYER->value,
    ], [
        'Accept-Language' => 'zh_CN',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', __('auth.invalid_credentials', [], 'zh_CN'));
});

test('legacy Accept-Language es aliases to zh_CN when spanish is not supported', function () {
    User::factory()->create([
        'email' => 'u@example.com',
        'password' => 'secret123',
        'role' => UserRole::BUYER->value,
    ]);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/login', [
        'email' => 'u@example.com',
        'password' => 'wrong-password',
        'role' => UserRole::BUYER->value,
    ], [
        'Accept-Language' => 'es',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', __('auth.invalid_credentials', [], 'zh_CN'));
});

test('unsupported Accept-Language falls back to app fallback locale', function () {
    User::factory()->create([
        'email' => 'u@example.com',
        'password' => 'secret123',
        'role' => UserRole::BUYER->value,
    ]);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/login', [
        'email' => 'u@example.com',
        'password' => 'wrong-password',
        'role' => UserRole::BUYER->value,
    ], [
        'Accept-Language' => 'fr, fr-FR;q=0.9',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', __('auth.invalid_credentials', [], 'en'));
});

test('X-App-Locale overrides Accept-Language when enabled', function () {
    User::factory()->create([
        'email' => 'u@example.com',
        'password' => 'secret123',
        'role' => UserRole::BUYER->value,
    ]);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/login', [
        'email' => 'u@example.com',
        'password' => 'wrong-password',
        'role' => UserRole::BUYER->value,
    ], [
        'Accept-Language' => 'en',
        'X-App-Locale' => 'zh_CN',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', __('auth.invalid_credentials', [], 'zh_CN'));
});

test('validation errors use locale from Accept-Language', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/login', [
        'email' => 'u@example.com',
        'role' => UserRole::BUYER->value,
    ], [
        'Accept-Language' => 'zh_CN',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.password.0', __('validation.required', ['attribute' => 'password'], 'zh_CN'));
});

test('mutating request resolves user preferred_language before X-App-Locale', function () {
    $this->seed(CurrencySeeder::class);

    $user = User::factory()->create([
        'preferred_language' => 'zh_CN',
    ]);

    Passport::actingAs($user);

    /** @var TestCase $this */
    $response = $this->patchJson('/api/v1/me/preferences', [
        'currency_code' => 'EUR',
    ], [
        'X-App-Locale' => 'en',
        'Accept-Language' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', __('common.updated', [], 'zh_CN'));
});

test('protected route resolves locale header before user preferred_language', function () {
    $user = User::factory()->create([
        'preferred_language' => 'en',
    ]);

    Passport::actingAs($user);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/me', [
        'Accept-Language' => 'en',
        'X-App-Locale' => 'zh_CN',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', __('api.user_details', [], 'zh_CN'));
});

test('protected GET resolves Accept-Language before user preferred_language', function () {
    $user = User::factory()->create([
        'preferred_language' => 'en',
    ]);

    Passport::actingAs($user);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/me', [
        'Accept-Language' => 'ar',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', __('api.user_details', [], 'ar'));
});
