<?php

namespace App\Services\Auth;

use App\Enums\MailTemplate;
use App\Exceptions\Auth\EmailVerificationException;
use App\Models\User;
use App\Services\Mailing\MailingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    /**
     * @return array{verification_token: string, code_expiry_time: int}
     */
    public function sendChallenge(User $user): array
    {
        $plainToken = (string) Str::uuid();
        $otp = $this->generateOtp();
        $ttlMinutes = $this->ttlMinutes();

        $this->storeChallenge($plainToken, $user->id, $otp, $ttlMinutes);
        $this->dispatchOtp($user->email, $otp, $ttlMinutes);

        return [
            'verification_token' => $plainToken,
            'code_expiry_time' => $ttlMinutes,
        ];
    }

    public function verify(string $verificationToken, string $otp): User
    {
        [$user] = $this->resolveChallenge($verificationToken, $otp);

        if ($user->hasVerifiedEmail()) {
            $this->forgetChallenge($verificationToken, $user->id);

            throw new EmailVerificationException(
                'account.email_verification_already_verified',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->forgetChallenge($verificationToken, $user->id);

        return $user->fresh();
    }

    /**
     * @return array{verification_token: string, code_expiry_time: int}
     */
    public function resend(string $verificationToken): array
    {
        $payload = Cache::get($this->cacheKey($verificationToken));

        if (! is_array($payload) || ! isset($payload['user_id'])) {
            throw new EmailVerificationException(
                'account.email_verification_token_invalid',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = User::query()->find($payload['user_id']);

        if ($user === null) {
            throw new EmailVerificationException(
                'account.email_verification_token_invalid',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->hasVerifiedEmail()) {
            $this->forgetChallenge($verificationToken, $user->id);

            throw new EmailVerificationException(
                'account.email_verification_already_verified',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $cooldownKey = $this->cooldownCacheKey($user->id);

        if (Cache::has($cooldownKey)) {
            $availableAtTs = (int) Cache::get($cooldownKey);

            throw new EmailVerificationException(
                'account.email_verification_resend_wait',
                Response::HTTP_TOO_MANY_REQUESTS,
                [
                    'retry_after_seconds' => max(0, $availableAtTs - now()->timestamp),
                    'available_at' => Carbon::createFromTimestamp($availableAtTs)->toIso8601String(),
                ],
            );
        }

        $otp = $this->generateOtp();
        $ttlMinutes = $this->ttlMinutes();

        $this->storeChallenge($verificationToken, $user->id, $otp, $ttlMinutes);
        $this->dispatchOtp($user->email, $otp, $ttlMinutes);
        $this->applyResendCooldown($user->id);

        return [
            'verification_token' => $verificationToken,
            'code_expiry_time' => $ttlMinutes,
        ];
    }

    /**
     * @return array{0: User}
     */
    private function resolveChallenge(string $verificationToken, string $otp): array
    {
        $payload = Cache::get($this->cacheKey($verificationToken));

        if (! is_array($payload) || ! isset($payload['user_id'], $payload['otp'])) {
            throw new EmailVerificationException(
                'account.email_verification_invalid_otp',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $storedOtp = decrypt($payload['otp']);

        if (! is_string($storedOtp) || ! hash_equals($storedOtp, $otp)) {
            throw new EmailVerificationException(
                'account.email_verification_invalid_otp',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = User::query()->find($payload['user_id']);

        if ($user === null) {
            throw new EmailVerificationException(
                'account.email_verification_token_invalid',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return [$user];
    }

    private function storeChallenge(string $verificationToken, int $userId, string $otp, int $ttlMinutes): void
    {
        Cache::put($this->cacheKey($verificationToken), [
            'user_id' => $userId,
            'otp' => encrypt($otp),
        ], now()->addMinutes($ttlMinutes));
    }

    private function dispatchOtp(string $email, string $otp, int $ttlMinutes): void
    {
        $this->mailingService->send($email, MailTemplate::EmailVerification, [
            'otp' => $otp,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    private function forgetChallenge(string $verificationToken, int $userId): void
    {
        Cache::forget($this->cacheKey($verificationToken));
        Cache::forget($this->cooldownCacheKey($userId));
    }

    private function applyResendCooldown(int $userId): void
    {
        $resendSeconds = config('account.email_verification_resend_seconds');
        $availableAt = now()->addSeconds($resendSeconds);
        Cache::put($this->cooldownCacheKey($userId), $availableAt->timestamp, $resendSeconds);
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function ttlMinutes(): int
    {
        return (int) config('account.email_verification_token_ttl_minutes');
    }

    private function cacheKey(string $verificationToken): string
    {
        return 'email_verification:'.$verificationToken;
    }

    private function cooldownCacheKey(int $userId): string
    {
        return 'email_verification_resend_cooldown:'.$userId;
    }
}
