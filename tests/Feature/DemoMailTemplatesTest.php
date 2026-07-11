<?php

use App\Enums\MailTemplate;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Models\Contact;
use App\Models\User;
use App\Services\Auth\PasswordChangedNotificationService;
use App\Services\Contact\ContactNotificationService;
use App\Services\Manufacturer\ManufacturerActivationReminderService;
use App\Services\Manufacturer\ManufacturerFirstPaymentReminderService;
use App\Services\Registration\BuyerRegistrationReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
    Queue::fake([SendMailJob::class]);
});

test('password change sends confirmation email', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
        'password' => Hash::make('old-password'),
    ]);

    app(PasswordChangedNotificationService::class)->notify($user, Request::create('/', 'POST', [], [], [], [
        'HTTP_USER_AGENT' => 'PHPUnit',
        'REMOTE_ADDR' => '127.0.0.1',
    ]));

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $user->email
        && $job->template === MailTemplate::PasswordChanged->value);
});

test('contact form notifies admins by email', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

    $contact = Contact::query()->create([
        'name' => 'James Chen',
        'email' => 'james@example.com',
        'company_name' => 'Global Parts Co.',
        'inquiry_type' => 'general',
        'message' => 'Need stainless steel fasteners.',
        'is_read' => false,
    ]);

    app(ContactNotificationService::class)->notifyAdmins($contact);

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $admin->email
        && $job->template === MailTemplate::AdminNewInquiry->value);
});

test('buyer registration reminder command queues email once', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
        'email_verified_at' => null,
        'created_at' => now()->subDays(5),
        'buyer_registration_reminder_sent_at' => null,
    ]);

    app(BuyerRegistrationReminderService::class)->sendReminder($user);

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $user->email
        && $job->template === MailTemplate::BuyerRegistrationReminder->value);

    expect($user->fresh()->buyer_registration_reminder_sent_at)->not->toBeNull();
});

test('manufacturer activation reminder command queues email for approved manufacturers', function () {
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::APPROVED,
        'manufacture_status_at' => now()->subDays(5),
        'manufacturer_activation_reminder_sent_at' => null,
    ]);

    app(ManufacturerActivationReminderService::class)->sendReminder($manufacturer);

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $manufacturer->email
        && $job->template === MailTemplate::ManufacturerActivationReminder->value);
});

test('manufacturer first payment reminder queues email once for approved manufacturers without subscription', function () {
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::APPROVED,
        'manufacture_status_at' => now()->subDays(5),
        'manufacturer_first_payment_reminder_sent_at' => null,
    ]);

    app(ManufacturerFirstPaymentReminderService::class)->sendReminder($manufacturer);

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === $manufacturer->email
        && $job->template === MailTemplate::ManufacturerFirstPaymentReminder->value);

    expect($manufacturer->fresh()->manufacturer_first_payment_reminder_sent_at)->not->toBeNull();

    Queue::fake([SendMailJob::class]);
    app(ManufacturerFirstPaymentReminderService::class)->sendReminder($manufacturer->fresh());
    Queue::assertNothingPushed();
});

test('manufacturer first payment reminders artisan command dispatches eligible reminders', function () {
    User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::APPROVED,
        'manufacture_status_at' => now()->subDays(5),
        'manufacturer_first_payment_reminder_sent_at' => null,
    ]);

    Artisan::call('manufacturer:send-first-payment-reminders');

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->template === MailTemplate::ManufacturerFirstPaymentReminder->value);
});

test('buyer registration reminders artisan command dispatches eligible reminders', function () {
    User::factory()->create([
        'role' => UserRole::BUYER->value,
        'email_verified_at' => null,
        'created_at' => now()->subDays(5),
        'buyer_registration_reminder_sent_at' => null,
    ]);

    Artisan::call('registration:send-buyer-reminders');

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->template === MailTemplate::BuyerRegistrationReminder->value);
});
