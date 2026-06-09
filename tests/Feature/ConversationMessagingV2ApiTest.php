<?php

declare(strict_types=1);

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationActivityLog;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('conversation create stores optional name and creator id', function (): void {
    $creator = User::factory()->admin()->create();
    $other = User::factory()->create();

    Passport::actingAs($creator);

    $response = $this->postJson('/api/v1/conversations', [
        'name' => 'Procurement Thread',
        'participant_ids' => [$creator->id, $other->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Procurement Thread')
        ->assertJsonPath('data.created_by', $creator->id);

    expect(ConversationActivityLog::query()->where('action', 'conversation.created')->count())->toBe(1);
});

test('participant can rename conversation', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $conversation = Conversation::factory()->create(['name' => 'Old Name', 'created_by' => $userA->id]);
    $conversation->participants()->attach([$userA->id, $userB->id]);

    Passport::actingAs($userB);

    $this->patchJson("/api/v1/conversations/{$conversation->id}", [
        'name' => 'New Name',
    ])->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    expect(ConversationActivityLog::query()->where('action', 'conversation.renamed')->count())->toBe(1);
});

test('participant can add another participant', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $newUser = User::factory()->create();

    $conversation = Conversation::factory()->create(['created_by' => $userA->id]);
    $conversation->participants()->attach([$userA->id, $userB->id]);

    Passport::actingAs($userB);

    $this->postJson("/api/v1/conversations/{$conversation->id}/participants", [
        'participant_ids' => [$newUser->id],
    ])->assertOk()
        ->assertJsonPath('success', true);

    expect($conversation->fresh()->participants()->pluck('users.id')->all())
        ->toContain($newUser->id);
    expect(ConversationActivityLog::query()->where('action', 'participants.added')->count())->toBe(1);
});

test('conversation show returns minimal payload without embedded messages list', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $conversation = Conversation::factory()->create(['name' => 'Thread', 'created_by' => $userA->id]);
    $conversation->participants()->attach([$userA->id, $userB->id]);
    ConversationActivityLog::query()->create([
        'conversation_id' => $conversation->id,
        'actor_id' => $userA->id,
        'action' => 'conversation.created',
        'data' => ['name' => 'Thread'],
    ]);

    Passport::actingAs($userA);

    $response = $this->getJson("/api/v1/conversations/{$conversation->id}");

    $response->assertOk()
        ->assertJsonMissingPath('data.messages')
        ->assertJsonPath('data.name', 'Thread')
        ->assertJsonPath('data.activity_logs.0.action', 'conversation.created');
});

test('messages index returns latest ten with before_id pagination', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $conversation = Conversation::factory()->create(['created_by' => $userA->id]);
    $conversation->participants()->attach([$userA->id, $userB->id]);

    foreach (range(1, 12) as $index) {
        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userA->id,
            'body' => 'Message '.$index,
        ]);
    }

    Passport::actingAs($userA);

    $firstPage = $this->getJson("/api/v1/conversations/{$conversation->id}/messages");
    $firstPage->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.has_more_older', true);

    $nextBeforeId = $firstPage->json('meta.next_before_id');

    $this->getJson("/api/v1/conversations/{$conversation->id}/messages?before_id={$nextBeforeId}&per_page=10")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('conversation index includes unread flag and last message sent timestamp', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $conversation = Conversation::factory()->create(['created_by' => $userA->id]);
    $conversation->participants()->attach([$userA->id, $userB->id]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $userB->id,
        'body' => 'Unread message',
    ]);

    Passport::actingAs($userA);

    $response = $this->getJson('/api/v1/conversations');

    $response->assertOk()
        ->assertJsonPath('data.0.is_unread', true);

    expect($response->json('data.0.last_message_sent_at'))->not->toBeNull();
});

test('fetching latest messages marks conversation as read for logged user', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $conversation = Conversation::factory()->create(['created_by' => $userA->id]);
    $conversation->participants()->attach([$userA->id, $userB->id]);

    Message::factory()->count(2)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $userB->id,
    ]);

    Passport::actingAs($userA);

    $this->getJson("/api/v1/conversations/{$conversation->id}/messages")
        ->assertOk();

    $conversationList = $this->getJson('/api/v1/conversations');
    $conversationList->assertOk()
        ->assertJsonPath('data.0.is_unread', false);
});

test('attachment only message is accepted and stores metadata', function (): void {
    Event::fake([MessageSent::class]);
    Storage::fake('public');

    $sender = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['created_by' => $sender->id]);
    $conversation->participants()->attach([$sender->id, $other->id]);

    Passport::actingAs($sender);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
        'attachments' => [
            UploadedFile::fake()->create('spec.pdf', 120, 'application/pdf'),
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.body', null)
        ->assertJsonPath('data.attachments.0.original_name', 'spec.pdf');

    expect(MessageAttachment::query()->count())->toBe(1);
    Event::assertDispatched(MessageSent::class);
});

test('message body or attachment is required', function (): void {
    $sender = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['created_by' => $sender->id]);
    $conversation->participants()->attach([$sender->id, $other->id]);

    Passport::actingAs($sender);

    $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

test('pair conversation uniqueness blocks duplicate when two participant uniqueness enabled', function (): void {
    config()->set('messaging.conversation_uniqueness.two_participants', true);
    config()->set('messaging.conversation_uniqueness.group_participants', false);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Passport::actingAs($user1);

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id],
    ])->assertCreated();

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['participant_ids']);
});

test('pair uniqueness can be disabled to allow duplicates', function (): void {
    config()->set('messaging.conversation_uniqueness.two_participants', false);
    config()->set('messaging.conversation_uniqueness.group_participants', false);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Passport::actingAs($user1);

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id],
    ])->assertCreated();

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id],
    ])->assertCreated();
});

test('group uniqueness implies pair uniqueness when group toggle enabled', function (): void {
    config()->set('messaging.conversation_uniqueness.two_participants', false);
    config()->set('messaging.conversation_uniqueness.group_participants', true);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Passport::actingAs($user1);

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id],
    ])->assertCreated();

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id],
    ])->assertStatus(422);
});

test('group duplicates are allowed when only two participant uniqueness enabled', function (): void {
    config()->set('messaging.conversation_uniqueness.two_participants', true);
    config()->set('messaging.conversation_uniqueness.group_participants', false);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    Passport::actingAs($user1);

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id, $user3->id],
    ])->assertCreated();

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$user1->id, $user2->id, $user3->id],
    ])->assertCreated();
});

test('adding participants is blocked when resulting set already exists and uniqueness applies', function (): void {
    config()->set('messaging.conversation_uniqueness.two_participants', false);
    config()->set('messaging.conversation_uniqueness.group_participants', true);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $conversationA = Conversation::factory()->create(['created_by' => $user1->id]);
    $conversationA->participants()->attach([$user1->id, $user2->id, $user3->id]);

    $conversationB = Conversation::factory()->create(['created_by' => $user1->id]);
    $conversationB->participants()->attach([$user1->id, $user2->id]);

    Passport::actingAs($user1);

    $this->postJson("/api/v1/conversations/{$conversationB->id}/participants", [
        'participant_ids' => [$user3->id],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['participant_ids']);
});
