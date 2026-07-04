<?php

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\MailTemplate;
use App\Jobs\SendMailJob;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('buyer can register and receives bearer token', function () {
    Queue::fake();

    PlatformSetting::query()->updateOrCreate(
        ['group' => 'security'],
        ['payload' => ['require_email_verification' => false]],
    );

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/register', [
        'role' => UserRole::BUYER->value,
        'first_name' => 'Buyer',
        'last_name' => 'User',
        'email' => 'buyer@example.com',
        'company_name' => 'Buyer Co',
        'country' => 'BD',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'terms_condition' => true,
        'device_name' => 'iphone',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.role', UserRole::BUYER->label());

    $this->assertDatabaseHas('users', [
        'email' => 'buyer@example.com',
        'role' => UserRole::BUYER->value,
        'status' => UserStatus::ACTIVE->value,
        'agreed_to_terms' => 1,
    ]);

    $user = User::query()->where('email', 'buyer@example.com')->firstOrFail();

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($user): bool {
        return $job->recipient === $user->email
            && $job->template === 'welcome'
            && $job->data['firstName'] === $user->first_name;
    });
});

test('manufacturer can register with required files and optional fields', function () {
    Queue::fake([SendMailJob::class, SendSupportTicketInAppNotificationJob::class]);
    Storage::fake('public');

    PlatformSetting::query()->updateOrCreate(
        ['group' => 'security'],
        ['payload' => ['require_email_verification' => false]],
    );

    User::factory()->create(['role' => UserRole::ADMIN->value, 'email' => 'admin@example.com']);

    $businessLicense = UploadedFile::fake()->create('license.pdf', 400, 'application/pdf');
    $factoryImageOne = UploadedFile::fake()->image('factory-1.jpg');
    $factoryImageTwo = UploadedFile::fake()->image('factory-2.png');

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/register', [
        'role' => UserRole::MANUFACTURER->value,
        'first_name' => 'Maker',
        'last_name' => 'One',
        'email' => 'manufacturer@example.com',
        'company_name' => 'Factory Co',
        'country' => 'US',
        'city' => 'Austin',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'terms_condition' => true,
        'company_website' => 'https://example.com',
        'notes' => 'Scale-ready factory',
        'bussiness_licence' => $businessLicense,
        'factory_images' => [$factoryImageOne, $factoryImageTwo],
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', __('auth.manufacturer.pending'))
        ->assertJsonPath('data', null)
        ->assertJsonPath('manufacture_status', UserManuFactureStatus::PENDING->value);

    expect($response->json('data'))->toBeNull();

    $this->assertDatabaseHas('users', [
        'email' => 'manufacturer@example.com',
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::PENDING->value,
        'status' => UserStatus::PENDING->value,
    ]);

    $user = User::query()->where('email', 'manufacturer@example.com')->firstOrFail();
    expect($user->manufacture_status_at)->not->toBeNull();

    $this->assertDatabaseHas('companies', [
        'user_id' => $user->id,
        'company_name' => 'Factory Co',
        'country' => 'US',
        'city' => 'Austin',
        'company_website' => 'https://example.com',
    ]);

    expect($user->factoryImages()->count())->toBe(2);

    $info = $user->company()->firstOrFail();
    expect($info->bussiness_license)->not->toBeNull();
    expect(Storage::disk('public')->exists($info->bussiness_license))->toBeTrue();

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job) => $job->recipient === 'admin@example.com'
        && $job->template === MailTemplate::ManufacturerRegisteredAdmin->value);

    Queue::assertPushed(SendSupportTicketInAppNotificationJob::class, fn (SendSupportTicketInAppNotificationJob $job) => $job->type === 'manufacturer.registered');
});

test('admin role registration is rejected', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/register', [
        'role' => UserRole::ADMIN->value,
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => 'admin@example.com',
        'company_name' => 'Admin Co',
        'country' => 'US',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'terms_condition' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('manufacturer registration requires business licence and city', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/register', [
        'role' => UserRole::MANUFACTURER->value,
        'first_name' => 'Maker',
        'last_name' => 'One',
        'email' => 'm2@example.com',
        'company_name' => 'Factory Co',
        'country' => 'US',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'terms_condition' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['city', 'bussiness_licence']);
});

test('forgot and reset password endpoints work', function () {
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => 'old-password',
        'role' => UserRole::BUYER->value,
    ]);

    /** @var TestCase $this */
    $forgot = $this->postJson('/api/v1/forgot-password', [
        'email' => $user->email,
    ]);

    $forgot->assertOk()
        ->assertJsonPath('message', __('api.password_reset_otp_sent_generic'));

    $otp = null;
    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use (&$otp, $user): bool {
        $otp = $job->data['otp'] ?? null;

        return $job->recipient === $user->email
            && $job->template === 'password-reset-otp';
    });
    expect($otp)->toBeString()->toHaveLength(6);

    $reset = $this->postJson('/api/v1/reset-password', [
        'otp' => $otp,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $reset->assertOk()
        ->assertJsonPath('message', __('passwords.reset'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('forgot password returns same generic message for unknown email', function () {
    Mail::fake();

    /** @var TestCase $this */
    $this->postJson('/api/v1/forgot-password', [
        'email' => 'noone@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('message', __('api.password_reset_otp_sent_generic'));

    Mail::assertNothingSent();
});

test('manufacturer login is forbidden while pending', function () {
    $password = 'password';
    $user = User::factory()->manufacturer()->create([
        'password' => $password,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    /** @var TestCase $this */
    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => $password,
        'role' => UserRole::MANUFACTURER->value,
        'device_name' => 'tests',
    ])->assertForbidden()
        ->assertJsonPath('message', __('auth.manufacturer.login-pending'));
});

test('manufacturer login succeeds when approved', function () {
    $password = 'password';
    $user = User::factory()->manufacturerApproved()->create([
        'password' => $password,
    ]);

    /** @var TestCase $this */
    $login = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => $password,
        'role' => UserRole::MANUFACTURER->value,
        'device_name' => 'tests',
    ]);

    $login->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.manufacture_status', UserManuFactureStatus::APPROVED->value);

    expect($login->json('data.access_token'))->toBeString()->not->toBeEmpty();
});

test('manufacturer login when rejected returns reason', function () {
    $password = 'password';
    $user = User::factory()->manufacturer()->create([
        'password' => $password,
        'manufacture_status' => UserManuFactureStatus::REJECTED,
        'manufacture_status_reason' => 'Incomplete documents',
    ]);

    /** @var TestCase $this */
    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => $password,
        'role' => UserRole::MANUFACTURER->value,
        'device_name' => 'tests',
    ])->assertForbidden()
        ->assertJsonPath('message', __('auth.manufacturer.rejected'))
        ->assertJsonPath('data.rejection_reason', 'Incomplete documents');
});
