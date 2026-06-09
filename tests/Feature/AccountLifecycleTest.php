<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\AccountRestoreOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('admin cannot request account deletion', function () {
    $user = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $token = $user->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/account/delete-request', [
            'password' => 'password',
        ])
        ->assertForbidden()
        ->assertJsonPath('message', __('account.admin_cannot_modify'));
});

test('buyer can schedule deletion and login is blocked during grace with restore hint', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
    ]);
    $token = $user->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/account/delete-request', [
            'password' => 'password',
        ])
        ->assertOk();

    $user->refresh();
    expect($user->deleted_at)->not->toBeNull();
    expect($user->status)->toBe(UserStatus::ScheduledDeletion);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => UserRole::BUYER->value,
        'device_name' => 'tests',
    ])
        ->assertForbidden()
        ->assertJsonPath('message', __('account.deletion_restore_login'));
});

test('restore delete flow sends otp and clears deleted_at', function () {
    Mail::fake();

    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
        'deleted_at' => now(),
        'deleted_reason' => 'test',
    ]);

    /** @var TestCase $this */
    $this->postJson('/api/v1/account/restore-delete/request', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    Mail::assertSent(AccountRestoreOtpMail::class);

    $cacheKey = 'account_restore_otp:'.$user->id;
    $hash = Cache::get($cacheKey);
    expect($hash)->toBeString();

    $otp = null;
    Mail::assertSent(AccountRestoreOtpMail::class, function (AccountRestoreOtpMail $mail) use (&$otp): bool {
        $otp = $mail->otp;

        return true;
    });

    expect($otp)->toBeString()->toHaveLength(6);

    $this->postJson('/api/v1/account/restore-delete/verify', [
        'email' => $user->email,
        'otp' => $otp,
    ])->assertOk();

    expect($user->fresh()->deleted_at)->toBeNull();
    expect($user->fresh()->status)->toBe(UserStatus::ACTIVE);
});

test('restore delete otp request returns 429 during resend cooldown then allows after window', function () {
    Mail::fake();

    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
        'deleted_at' => now(),
        'deleted_reason' => 'test',
    ]);

    /** @var TestCase $this */
    $this->postJson('/api/v1/account/restore-delete/request', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    $cooldownResponse = $this->postJson('/api/v1/account/restore-delete/request', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $cooldownResponse->assertStatus(429)
        ->assertJsonPath('message', __('account.restore_otp_resend_wait'))
        ->assertJsonPath('data.retry_after_seconds', fn ($v) => is_int($v) && $v >= 0)
        ->assertJsonStructure(['data' => ['available_at']]);

    Mail::assertSentTimes(AccountRestoreOtpMail::class, 1);

    $this->travel(61)->seconds();

    $this->postJson('/api/v1/account/restore-delete/request', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    Mail::assertSentTimes(AccountRestoreOtpMail::class, 2);
});

test('finalize scheduled deletions command removes user after grace period', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
        'deleted_at' => now()->subDays(config('account.deletion_grace_days'))->subDay(),
    ]);

    $this->artisan('users:finalize-scheduled-deletions')->assertSuccessful();

    expect(User::query()->find($user->id))->toBeNull();
});

test('successful login records login history', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
    ]);

    /** @var TestCase $this */
    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => UserRole::BUYER->value,
        'device_name' => 'iphone-tests',
    ])->assertOk();

    expect($user->loginHistories()->count())->toBe(1);
    expect($user->loginHistories()->first()->device_name)->toBe('iphone-tests');
});

test('two factor enable returns qr code svg', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
    ]);
    $token = $user->createToken('test')->accessToken;

    /** @var TestCase $this */
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/two-factor/enable', [
            'password' => 'password',
        ]);

    $response->assertOk();
    expect($response->json('data.qr_code_svg'))->toBeString()->not->toBeEmpty();
});

test('authenticated user can change password', function () {
    $user = User::factory()->create([
        'role' => UserRole::BUYER->value,
    ]);
    $token = $user->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/account/change-password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertOk();

    $user->refresh();
    expect(Hash::check('new-password-123', $user->password))->toBeTrue();
});
