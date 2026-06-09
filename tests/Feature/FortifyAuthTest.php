<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fortify can authenticate user via login endpoint', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    /** @var \Tests\TestCase $this */
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});
