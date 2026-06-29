<?php

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendMailJob;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
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

    config([
        'app.frontend_url' => 'http://localhost:3000',
        'broadcasting.default' => 'null',
    ]);
});

test('admin can create support ticket for manufacturer with department and priority', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::PENDING,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/support-tickets", [
        'subject' => 'Registration follow-up required',
        'message' => 'Please provide updated business license documentation.',
        'department_type' => TicketDepartmentType::Account->value,
        'priority' => TicketPriority::High->value,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.subject', 'Registration follow-up required')
        ->assertJsonPath('data.priority', TicketPriority::High->value)
        ->assertJsonPath('data.department_type', TicketDepartmentType::Account->value);

    $ticket = Ticket::query()->where('user_id', $manufacturer->id)->first();
    expect($ticket)->not->toBeNull();
    expect($ticket->assigned_to)->toBe($admin->id);
    expect($ticket->messages)->toHaveCount(1);

    Queue::assertPushed(SendMailJob::class);

    Queue::assertPushed(SendSupportTicketInAppNotificationJob::class, function (SendSupportTicketInAppNotificationJob $job) use ($manufacturer, $admin): bool {
        return $job->recipientId === $manufacturer->id
            && $job->senderId === $admin->id
            && $job->type === 'support.ticket.created'
            && str_contains((string) $job->actionUrl, '/dashboard/manufacturer/support-tickets/');
    });
});

test('admin create support ticket persists in-app notification for manufacturer', function () {
    Queue::fake([SendMailJob::class, SendSupportTicketInAppNotificationJob::class]);

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/support-tickets", [
        'subject' => 'Documents required',
        'message' => 'Please upload your latest business license.',
    ]);

    $response->assertCreated();
    $ticketId = (int) $response->json('data.id');

    $job = Queue::pushed(SendSupportTicketInAppNotificationJob::class)->first();
    expect($job)->not->toBeNull();

    app()->call([$job, 'handle']);

    $notification = UserNotification::query()
        ->where('user_id', $manufacturer->id)
        ->where('type', 'support.ticket.created')
        ->first();

    expect($notification)->not->toBeNull();
    expect($notification->sender_id)->toBe($admin->id);
    expect($notification->action_url)->toBe("http://localhost:3000/dashboard/manufacturer/support-tickets/{$ticketId}");
});

test('admin create support ticket requires subject and message', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/support-tickets", [
        'subject' => '',
        'message' => '',
    ])->assertUnprocessable();
});

test('non admin cannot create manufacturer support ticket', function () {
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);

    Passport::actingAs($buyer);

    /** @var TestCase $this */
    $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/support-tickets", [
        'subject' => 'Test',
        'message' => 'Test message',
    ])->assertForbidden();
});
