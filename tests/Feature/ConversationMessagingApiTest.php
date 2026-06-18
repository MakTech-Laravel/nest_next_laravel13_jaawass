<?php

declare(strict_types=1);

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('guest cannot list conversations', function (): void {
    $this->getJson('/api/v1/conversations')->assertUnauthorized();
});

test('authenticated user lists only conversations they participate in', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = Conversation::factory()->create();
    $mine->participants()->attach([$user->id, $other->id]);

    $theirs = Conversation::factory()->create();
    $theirs->participants()->attach([$other->id, User::factory()->create()->id]);

    Passport::actingAs($user);

    $response = $this->getJson('/api/v1/conversations');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');
});

test('authenticated user can create a conversation when they are a participant', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();

    Passport::actingAs($buyer);

    $response = $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$buyer->id, $manufacturer->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.participants.0.id', $buyer->id);

    expect(Conversation::query()->count())->toBe(1)
        ->and(Conversation::query()->first()?->participants)->toHaveCount(2);
});

test('user cannot create a conversation when they omit themselves from participants', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();

    Passport::actingAs($buyer);

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$manufacturer->id, User::factory()->create()->id],
    ])->assertForbidden();
});

test('participant can send a message and message sent event is dispatched', function (): void {
    Event::fake([MessageSent::class]);

    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

    Passport::actingAs($manufacturer);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
        'body' => 'Hello from manufacturer',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.body', 'Hello from manufacturer');

    expect(Message::query()->count())->toBe(1);

    Event::assertDispatched(MessageSent::class, function (MessageSent $event): bool {
        return $event->message->body === 'Hello from manufacturer';
    });
});

test('non participant cannot send a message', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $outsider = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

    Passport::actingAs($outsider);

    $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
        'body' => 'Not allowed',
    ])->assertForbidden();

    expect(Message::query()->count())->toBe(0);
});

test('non participant cannot view a conversation', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $outsider = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

    Passport::actingAs($outsider);

    $this->getJson("/api/v1/conversations/{$conversation->id}")
        ->assertForbidden();
});

test('participant is authorized for private chat room channel', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();

    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

    Passport::actingAs($buyer);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-chat.room.'.$conversation->id,
    ])->assertOk();
});

test('outsider is denied for private chat room channel', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $outsider = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

    Passport::actingAs($outsider);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-chat.room.'.$conversation->id,
    ])->assertForbidden();
});
