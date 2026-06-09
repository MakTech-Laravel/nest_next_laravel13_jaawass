<?php

use App\Events\UserNotificationCreated;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\UserNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('guest cannot list notifications', function () {
    $this->getJson('/api/v1/me/notifications')->assertUnauthorized();
});

test('authenticated user can list notifications', function () {
    $user = User::factory()->create();
    UserNotification::factory()->count(2)->create(['user_id' => $user->id]);
    UserNotification::factory()->create();

    Passport::actingAs($user);

    $response = $this->getJson('/api/v1/me/notifications');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

test('unread filter returns only unread notifications', function () {
    $user = User::factory()->create();
    UserNotification::factory()->create(['user_id' => $user->id, 'read_at' => null]);
    UserNotification::factory()->read()->create(['user_id' => $user->id]);

    Passport::actingAs($user);

    $response = $this->getJson('/api/v1/me/notifications?unread=1');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('user cannot mark another users notification as read', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = UserNotification::factory()->create(['user_id' => $other->id]);

    Passport::actingAs($user);

    $this->patchJson("/api/v1/me/notifications/{$notification->id}/read")
        ->assertNotFound();
});

test('user can mark own notification as read', function () {
    $user = User::factory()->create();
    $notification = UserNotification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

    Passport::actingAs($user);

    $this->patchJson("/api/v1/me/notifications/{$notification->id}/read")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all read updates unread for user only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    UserNotification::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => null]);
    UserNotification::factory()->create(['user_id' => $other->id, 'read_at' => null]);

    Passport::actingAs($user);

    $this->postJson('/api/v1/me/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('data.updated', 2);

    expect(UserNotification::query()->where('user_id', $user->id)->whereNull('read_at')->count())->toBe(0);
    expect(UserNotification::query()->where('user_id', $other->id)->whereNull('read_at')->count())->toBe(1);
});

test('user notification service dispatches broadcast event', function () {
    Event::fake([UserNotificationCreated::class]);

    $user = User::factory()->create();
    $service = app(UserNotificationService::class);
    $notification = $service->createForUser($user, 'test.type', 'Title', 'Body', ['k' => 'v']);

    expect($notification->exists)->toBeTrue();

    Event::assertDispatched(UserNotificationCreated::class, function (UserNotificationCreated $event) use ($notification): bool {
        return $event->notification->is($notification);
    });
});

test('user notification service create persists without broadcast until broadcast is called', function () {
    Event::fake([UserNotificationCreated::class]);

    $user = User::factory()->create();
    $service = app(UserNotificationService::class);
    $notification = $service->create($user, 'deferred.type', 'T', 'B');

    Event::assertNotDispatched(UserNotificationCreated::class);

    $service->broadcast($notification);

    Event::assertDispatched(UserNotificationCreated::class, function (UserNotificationCreated $event) use ($notification): bool {
        return $event->notification->is($notification);
    });
});

test('user notification service notify records sender when provided', function () {
    Event::fake([UserNotificationCreated::class]);

    $recipient = User::factory()->create();
    $sender = User::factory()->create();
    $service = app(UserNotificationService::class);
    $notification = $service->notify($recipient, 'msg', 'Hi', 'Body', [], null, $sender);

    expect($notification->sender_id)->toBe($sender->id)
        ->and($notification->isFromSystem())->toBeFalse();
});

test('post test broadcast route creates notification and dispatches event', function () {
    Event::fake([UserNotificationCreated::class]);

    $user = User::factory()->create();
    $token = $user->createToken('test')->accessToken;

    $this->postJson('/api/v1/me/notifications/test-broadcast', [
        'title' => 'Pusher check',
        'origin' => 'system',
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->assertCreated()
        ->assertJsonPath('data.is_system', true)
        ->assertJsonPath('data.sender_id', null);

    Event::assertDispatched(UserNotificationCreated::class);
});

test('post test broadcast route with origin self sets sender', function () {
    Event::fake([UserNotificationCreated::class]);

    $user = User::factory()->create();
    $token = $user->createToken('test')->accessToken;

    $this->postJson('/api/v1/me/notifications/test-broadcast', [
        'origin' => 'self',
    ], [
        'Authorization' => 'Bearer '.$token,
    ])->assertCreated()
        ->assertJsonPath('data.is_system', false)
        ->assertJsonPath('data.sender_id', $user->id);
});
