<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Message;
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

test('guest cannot list admin conversations', function (): void {
    $this->getJson('/api/v1/admin/conversations')->assertUnauthorized();
});

test('non admin cannot list admin conversations', function (): void {
    $buyer = User::factory()->create();

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/admin/conversations')->assertForbidden();
});

test('admin lists only buyer manufacturer conversations', function (): void {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $otherBuyer = User::factory()->create();

    $buyerSellerConversation = Conversation::factory()->create();
    $buyerSellerConversation->participants()->attach([$buyer->id, $manufacturer->id]);
    Message::factory()->create([
        'conversation_id' => $buyerSellerConversation->id,
        'sender_id' => $buyer->id,
        'body' => 'Hello manufacturer',
    ]);

    $adminConversation = Conversation::factory()->create();
    $adminConversation->participants()->attach([$admin->id, $buyer->id]);

    $otherConversation = Conversation::factory()->create();
    $otherConversation->participants()->attach([$otherBuyer->id, User::factory()->create()->id]);

    Passport::actingAs($admin);

    $this->getJson('/api/v1/admin/conversations')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $buyerSellerConversation->id)
        ->assertJsonPath('data.0.last_message.body', 'Hello manufacturer');
});

test('admin can view messages for a buyer manufacturer conversation', function (): void {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();

    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $buyer->id,
        'body' => 'First message',
    ]);
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $manufacturer->id,
        'body' => 'Reply message',
    ]);

    Passport::actingAs($admin);

    $this->getJson("/api/v1/admin/conversations/{$conversation->id}/messages")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.body', 'First message')
        ->assertJsonPath('data.1.body', 'Reply message');
});

test('admin cannot view messages for conversations they are part of', function (): void {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$admin->id, $buyer->id]);

    Passport::actingAs($admin);

    $this->getJson("/api/v1/admin/conversations/{$conversation->id}/messages")
        ->assertForbidden()
        ->assertJsonPath('success', false);
});
