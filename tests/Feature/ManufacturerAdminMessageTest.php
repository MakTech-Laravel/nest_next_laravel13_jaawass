<?php

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendMailJob;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

test('admin can send message to manufacturer and ticket plus email are created', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::PENDING,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/send-message", [
        'subject' => 'Please clarify your factory address',
        'message' => 'We need your full factory address before we can continue the review.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.subject', 'Please clarify your factory address');

    $ticket = Ticket::query()->where('user_id', $manufacturer->id)->first();
    expect($ticket)->not->toBeNull();
    expect($ticket->assigned_to)->toBe($admin->id);
    expect($ticket->messages)->toHaveCount(1);
    expect($ticket->messages->first()->message)->toContain('full factory address');

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'manufacturer-admin-message';
    });
});

test('admin send message requires message body', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/send-message", [
        'message' => '',
    ])->assertUnprocessable();
});
