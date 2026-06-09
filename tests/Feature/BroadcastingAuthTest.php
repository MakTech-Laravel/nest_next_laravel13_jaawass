<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('broadcasting auth succeeds when channel user id matches authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('broadcasting-test')->accessToken;

    $response = $this->post('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-user.'.$user->id,
    ], [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['auth']);
});

test('broadcasting auth returns forbidden when channel targets another user', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $token = $alice->createToken('broadcasting-test')->accessToken;

    $response = $this->post('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-user.'.$bob->id,
    ], [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertForbidden();
});
