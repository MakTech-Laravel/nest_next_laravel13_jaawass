<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token as PassportToken;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('api v1 login issues bearer token and me/logout work', function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    $password = 'password';

    $user = User::factory()->create([
        'password' => $password,
        'role' => UserRole::BUYER->value,
    ]);

    /** @var TestCase $this */
    $login = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => $password,
        'role' => UserRole::BUYER->value,
        'device_name' => 'tests',
    ]);

    $login->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', $user->email);

    $accessToken = $login->json('data.access_token');
    expect($accessToken)->toBeString()->not->toBeEmpty();

    $me = $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->getJson('/api/v1/me');

    $me->assertOk()
        ->assertJsonPath('data.id', $user->id);

    $logout = $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->postJson('/api/v1/logout');

    $logout->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Logout successful');

    $latestToken = PassportToken::query()
        ->where('user_id', $user->id)
        ->latest('id')
        ->first();

    expect($latestToken)->not->toBeNull();
    expect((bool) $latestToken->revoked)->toBeTrue();

    Auth::forgetGuards();

    $meAfter = $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->getJson('/api/v1/me');

    $meAfter->assertUnauthorized();
});
