<?php

use App\Enums\MailTemplate;
use App\Enums\RfqSubmissionStatus;
use App\Enums\TicketStatus;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Mailing\MailTemplateRenderer;
use App\Services\Manufacturer\ManufacturerRegistrationNotificationService;
use App\Services\Manufacturer\ManufacturerStatusNotificationService;
use App\Services\Rfq\RfqNotificationService;
use App\Services\Support\SupportTicketNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
    Queue::fake([SendMailJob::class, SendSupportTicketInAppNotificationJob::class]);
});

test('rfq created sends email to manufacturer', function () {
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $product = Product::factory()->create(['user_id' => $manufacturer->id]);

    $conversation = Conversation::query()->create([
        'name' => 'RFQ test',
        'created_by' => $buyer->id,
    ]);

    $rfq = RfqSubmission::query()->create([
        'rfq_number' => 'RFQ-001',
        'buyer_id' => $buyer->id,
        'manufacturer_id' => $manufacturer->id,
        'product_id' => $product->id,
        'conversation_id' => $conversation->id,
        'quantity' => 100,
        'quantity_unit' => 'pieces',
        'status' => RfqSubmissionStatus::Pending->value,
    ]);

    app(RfqNotificationService::class)->notifyCreated($rfq);

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $manufacturer->email
        && $job->template === MailTemplate::RfqCreatedManufacturer->value);

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $buyer->email
        && $job->template === MailTemplate::RfqSubmittedBuyer->value);
});

test('support ticket created sends email to user and admin', function () {
    $user = User::factory()->create(['role' => UserRole::BUYER->value]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    $ticket = Ticket::query()->create([
        'user_id' => $user->id,
        'subject' => 'Need help with order',
        'department_type' => 'account',
        'priority' => 'medium',
        'status' => TicketStatus::Open->value,
    ]);

    app(SupportTicketNotificationService::class)->notifyCreated($ticket, $user, 'Please assist');

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $user->email
        && $job->template === MailTemplate::SupportTicketCreated->value);

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $admin->email
        && $job->template === MailTemplate::SupportTicketCreatedAdmin->value);
});

test('user support reply emails every admin and acknowledges the user', function () {
    $user = User::factory()->create(['role' => UserRole::BUYER->value]);
    $firstAdmin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $secondAdmin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    $ticket = Ticket::query()->create([
        'user_id' => $user->id,
        'subject' => 'Need help with order',
        'department_type' => 'account',
        'priority' => 'medium',
        'status' => TicketStatus::Open->value,
        'assigned_to' => null,
    ]);

    app(SupportTicketNotificationService::class)->notifyReply($ticket, $user, 'Here is more information.');

    foreach ([$firstAdmin, $secondAdmin] as $admin) {
        Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job): bool => $job->recipient === $admin->email
            && $job->template === MailTemplate::SupportTicketReplyAdmin->value);
    }

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job): bool => $job->recipient === $user->email
        && $job->template === MailTemplate::SupportTicketReplyReceived->value
        && $job->data['messageBodyPlain'] === 'Here is more information.');

    Queue::assertPushed(SendMailJob::class, 3);
});

test('admin support reply emails only the ticket owner', function () {
    $user = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $otherAdmin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    $ticket = Ticket::query()->create([
        'user_id' => $user->id,
        'subject' => 'Verification question',
        'department_type' => 'account',
        'priority' => 'medium',
        'status' => TicketStatus::WaitingOnCustomer->value,
        'assigned_to' => $admin->id,
    ]);

    app(SupportTicketNotificationService::class)->notifyReply($ticket, $admin, 'Please provide the missing document.');

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job): bool => $job->recipient === $user->email
        && $job->template === MailTemplate::SupportTicketReply->value);

    Queue::assertNotPushed(SendMailJob::class, fn (SendMailJob $job): bool => in_array(
        $job->recipient,
        [$admin->email, $otherAdmin->email],
        true,
    ));

    Queue::assertPushed(SendMailJob::class, 1);
});

test('support reply acknowledgement template renders', function () {
    $html = app(MailTemplateRenderer::class)->render(
        MailTemplate::SupportTicketReplyReceived->value,
        [
            'name' => 'Buyer One',
            'subject' => 'Order issue',
            'ticketNumber' => 'TKT-00001',
            'ticketSubject' => 'Order issue',
            'messageBodyPlain' => 'Additional details',
            'ctaUrl' => 'https://example.com/tickets/1',
            'ctaLabel' => 'View ticket',
        ],
    );

    expect($html)
        ->toContain('We received')
        ->toContain('TKT-00001')
        ->toContain('Additional details');
});

test('manufacturer approved sends email notification', function () {
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    app(ManufacturerStatusNotificationService::class)->notifyStatusChanged(
        $manufacturer,
        UserManuFactureStatus::APPROVED,
    );

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $manufacturer->email
        && $job->template === MailTemplate::ManufacturerApproved->value);
});

test('manufacturer registration notifies admins by email and in-app', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::PENDING,
        'first_name' => 'Maker',
        'last_name' => 'One',
        'email' => 'new-manufacturer@example.com',
    ]);
    $manufacturer->company()->create([
        'company_name' => 'Factory Co',
        'country' => 'US',
        'city' => 'Austin',
        'notes' => 'Scale-ready factory',
    ]);

    app(ManufacturerRegistrationNotificationService::class)
        ->notifyAdmins($manufacturer->fresh(['company']));

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $admin->email
        && $job->template === MailTemplate::ManufacturerRegisteredAdmin->value
        && str_contains((string) ($job->data['ctaUrl'] ?? ''), 'admin/manufacturer-registrations?manufacturer='.$manufacturer->id));

    Queue::assertPushed(SendSupportTicketInAppNotificationJob::class, fn (SendSupportTicketInAppNotificationJob $job) => $job->recipientId === $admin->id
        && $job->type === 'manufacturer.registered'
        && str_contains((string) $job->actionUrl, 'admin/manufacturer-registrations?manufacturer='.$manufacturer->id));
});
