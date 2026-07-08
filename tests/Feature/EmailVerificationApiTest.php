<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    Queue::fake([SendMailJob::class]);
});

function disableEmailVerificationRequirement(): void
{
    PlatformSetting::query()->updateOrCreate(
        ['group' => 'security'],
        ['payload' => ['require_email_verification' => false]],
    );
}

function registerBuyerForEmailVerification(): array
{
    $response = test()->postJson('/api/v1/register', [
        'role' => UserRole::BUYER->value,
        'first_name' => 'Buyer',
        'last_name' => 'User',
        'email' => 'verify-buyer@example.com',
        'company_name' => 'Buyer Co',
        'country' => 'BD',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'terms_condition' => true,
        'device_name' => 'iphone',
    ])->assertCreated();

    return [
        'verification_token' => $response->json('data.verification_token'),
        'user_id' => User::query()->where('email', 'verify-buyer@example.com')->value('id'),
    ];
}

test('buyer can verify email with otp and receives bearer token', function (): void {
    ['verification_token' => $token, 'user_id' => $userId] = registerBuyerForEmailVerification();

    $otp = null;
    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use (&$otp): bool {
        if ($job->template !== 'email-verification') {
            return false;
        }

        $otp = $job->data['otp'] ?? null;

        return is_string($otp);
    });

    expect($otp)->toBeString()->toHaveLength(6);

    $response = $this->postJson('/api/v1/email/verify', [
        'verification_token' => $token,
        'otp' => $otp,
        'device_name' => 'iphone',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.is_email_verified', true);

    expect($response->json('data.access_token'))->toBeString()->not->toBeEmpty();

    $user = User::query()->findOrFail($userId);
    expect($user->hasVerifiedEmail())->toBeTrue();
    expect(Cache::has('email_verification:'.$token))->toBeFalse();
});

test('verify email rejects invalid otp', function (): void {
    ['verification_token' => $token] = registerBuyerForEmailVerification();

    $this->postJson('/api/v1/email/verify', [
        'verification_token' => $token,
        'otp' => '000000',
    ])->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', __('account.email_verification_invalid_otp'));
});

test('resend email verification sends a new otp', function (): void {
    ['verification_token' => $token] = registerBuyerForEmailVerification();

    Queue::fake([SendMailJob::class]);

    $this->postJson('/api/v1/email/verification/resend', [
        'verification_token' => $token,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.verification_token', $token)
        ->assertJsonPath('message', __('account.email_verification_sent'));

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job): bool => $job->template === 'email-verification');
});

test('resend email verification enforces cooldown', function (): void {
    ['verification_token' => $token] = registerBuyerForEmailVerification();

    $this->postJson('/api/v1/email/verification/resend', [
        'verification_token' => $token,
    ])->assertOk();

    $this->postJson('/api/v1/email/verification/resend', [
        'verification_token' => $token,
    ])->assertStatus(429)
        ->assertJsonPath('message', __('account.email_verification_resend_wait'))
        ->assertJsonStructure(['data' => ['retry_after_seconds', 'available_at']]);
});

test('verified buyer can access protected buyer routes', function (): void {
    ['verification_token' => $token] = registerBuyerForEmailVerification();

    $otp = null;
    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use (&$otp): bool {
        if ($job->template !== 'email-verification') {
            return false;
        }

        $otp = $job->data['otp'] ?? null;

        return is_string($otp);
    });

    $verify = $this->postJson('/api/v1/email/verify', [
        'verification_token' => $token,
        'otp' => $otp,
    ])->assertOk();

    $accessToken = $verify->json('data.access_token');
    $user = User::query()->where('email', 'verify-buyer@example.com')->firstOrFail();

    Passport::actingAs($user);

    $this->getJson('/api/v1/buyer/orders', [
        'Authorization' => 'Bearer '.$accessToken,
    ])->assertOk();
});

test('buyer registration skips verification challenge when platform setting is disabled', function (): void {
    disableEmailVerificationRequirement();

    $this->postJson('/api/v1/register', [
        'role' => UserRole::BUYER->value,
        'first_name' => 'Buyer',
        'last_name' => 'User',
        'email' => 'no-verify@example.com',
        'company_name' => 'Buyer Co',
        'country' => 'BD',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'terms_condition' => true,
        'device_name' => 'iphone',
    ])->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonMissingPath('data.verification_token');
});

test('buyer login with unverified email returns verification token instead of access token', function (): void {
    $password = 'secret123';
    $user = User::factory()->unverified()->create([
        'password' => $password,
        'role' => UserRole::BUYER->value,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => $password,
        'role' => UserRole::BUYER->value,
        'device_name' => 'iphone',
    ])->assertOk()
        ->assertJsonPath('message', __('api.email_verification_required'))
        ->assertJsonPath('data.verification_token', fn ($token) => is_string($token) && $token !== '')
        ->assertJsonPath('data.code_expiry_time', fn ($ttl) => is_int($ttl) && $ttl > 0)
        ->assertJsonMissingPath('data.access_token')
        ->assertJsonMissingPath('data.token_type');

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job): bool => $job->recipient === $user->email
        && $job->template === 'email-verification');
});

test('manufacturer login with unverified email returns verification token instead of access token', function (): void {
    $password = 'secret123';
    $user = User::factory()->manufacturerApproved()->unverified()->create([
        'password' => $password,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => $password,
        'role' => UserRole::MANUFACTURER->value,
        'device_name' => 'iphone',
    ])->assertOk()
        ->assertJsonPath('message', __('api.email_verification_required'))
        ->assertJsonPath('data.verification_token', fn ($token) => is_string($token) && $token !== '')
        ->assertJsonPath('data.code_expiry_time', fn ($ttl) => is_int($ttl) && $ttl > 0)
        ->assertJsonMissingPath('data.access_token')
        ->assertJsonMissingPath('data.token_type');

    Queue::assertPushed(SendMailJob::class, fn (SendMailJob $job): bool => $job->recipient === $user->email
        && $job->template === 'email-verification');
});

test('buyer login with unverified email skips verification when platform setting is disabled', function (): void {
    disableEmailVerificationRequirement();

    $password = 'secret123';
    $user = User::factory()->unverified()->create([
        'password' => $password,
        'role' => UserRole::BUYER->value,
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => $password,
        'role' => UserRole::BUYER->value,
        'device_name' => 'iphone',
    ])->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonMissingPath('data.verification_token');
});
