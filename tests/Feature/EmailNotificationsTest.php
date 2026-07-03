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
use App\Services\Manufacturer\ManufacturerStatusNotificationService;
use App\Services\Rfq\RfqNotificationService;
use App\Services\Support\SupportTicketNotificationService;
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
