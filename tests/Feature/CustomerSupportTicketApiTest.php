<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('buyer can login and access customer support tickets', function (): void {
    $password = 'user@dev.com';

    $buyer = User::query()->where('email', 'user@dev.com')->where('role', UserRole::BUYER->value)->first();

    if ($buyer === null) {
        $buyer = User::factory()->create([
            'email' => 'user@dev.com',
            'password' => $password,
            'role' => UserRole::BUYER->value,
        ]);
    } else {
        $buyer->forceFill(['password' => bcrypt($password)])->save();
    }

    $login = $this->postJson('/api/v1/login', [
        'email' => 'user@dev.com',
        'password' => $password,
        'role' => UserRole::BUYER->value,
        'device_name' => 'pest',
    ]);

    $login->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer');

    $accessToken = $login->json('data.access_token');
    expect($accessToken)->toBeString()->not->toBeEmpty();

    $me = $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->getJson('/api/v1/me');

    $me->assertOk();

    $tickets = $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->getJson('/api/v1/customer-supports/tickets');

    $tickets->assertOk()
        ->assertJsonPath('success', true);
});

test('customer support tickets without token returns unauthorized', function (): void {
    $this->getJson('/api/v1/customer-supports/tickets')
        ->assertUnauthorized();
});

test('authenticated user can fetch customer support ticket options', function (): void {
    $buyer = User::factory()->create([
        'role' => UserRole::BUYER->value,
    ]);

    $this->actingAs($buyer, 'api')
        ->getJson('/api/v1/customer-supports/options')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'statuses' => [['value', 'label']],
                'priorities' => [['value', 'label']],
                'department_types' => [['value', 'label']],
            ],
        ])
        ->assertJsonPath('data.statuses.0.value', 'open')
        ->assertJsonPath('data.priorities.0.value', 'low')
        ->assertJsonPath('data.department_types.0.value', 'general');
});
