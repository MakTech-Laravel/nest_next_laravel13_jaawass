<?php

namespace App\Services\Account;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AccountRestoreService
{
    public function __construct(
        private readonly AccountRestoreNotificationService $accountRestoreNotificationService,
    ) {}

    public function requestOtp(string $email, string $password): AccountRestoreResult
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return new AccountRestoreResult(
                success: false,
                message: __('auth.invalid_credentials'),
                statusCode: HttpStatus::HTTP_UNAUTHORIZED,
            );
        }

        if ($user->role->isAdmin()) {
            return new AccountRestoreResult(
                success: false,
                message: __('account.admin_cannot_modify'),
                statusCode: HttpStatus::HTTP_FORBIDDEN,
            );
        }

        if ($user->deleted_at === null) {
            return new AccountRestoreResult(
                success: false,
                message: __('account.no_scheduled_deletion'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->isAwaitingPermanentDeletionPurge()) {
            return new AccountRestoreResult(
                success: false,
                message: __('account.deletion_processing'),
                statusCode: HttpStatus::HTTP_FORBIDDEN,
            );
        }

        $cooldownKey = $this->restoreOtpCooldownCacheKey($user->id);

        if (Cache::has($cooldownKey)) {
            $availableAtTs = (int) Cache::get($cooldownKey);
            $retryAfterSeconds = max(0, $availableAtTs - now()->timestamp);
            $availableAt = Carbon::createFromTimestamp($availableAtTs);

            return new AccountRestoreResult(
                success: false,
                message: __('account.restore_otp_resend_wait'),
                data: [
                    'retry_after_seconds' => $retryAfterSeconds,
                    'available_at' => $availableAt->toIso8601String(),
                ],
                statusCode: HttpStatus::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->restoreOtpCacheKey($user->id);
        Cache::put($cacheKey, Hash::make($otp), now()->addMinutes(config('account.restore_otp_ttl_minutes')));

        $this->accountRestoreNotificationService->sendOtp($user, $otp);

        $resendSeconds = config('account.restore_otp_resend_seconds');
        $availableAt = now()->addSeconds($resendSeconds);
        Cache::put($cooldownKey, $availableAt->timestamp, $resendSeconds);

        return new AccountRestoreResult(
            success: true,
            message: __('account.restore_otp_sent'),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function verifyOtp(string $email, string $otp): AccountRestoreResult
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return new AccountRestoreResult(
                success: false,
                message: __('account.restore_invalid_otp'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->role->isAdmin()) {
            return new AccountRestoreResult(
                success: false,
                message: __('account.admin_cannot_modify'),
                statusCode: HttpStatus::HTTP_FORBIDDEN,
            );
        }

        $cacheKey = $this->restoreOtpCacheKey($user->id);
        $hash = Cache::get($cacheKey);

        if (! is_string($hash) || ! Hash::check($otp, $hash)) {
            return new AccountRestoreResult(
                success: false,
                message: __('account.restore_invalid_otp'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        Cache::forget($cacheKey);
        Cache::forget($this->restoreOtpCooldownCacheKey($user->id));

        $user->forceFill([
            'deleted_at' => null,
            'deleted_reason' => null,
            'status' => $this->restoredUserStatus($user),
        ])->save();

        return new AccountRestoreResult(
            success: true,
            message: __('account.restore_success'),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    private function restoreOtpCacheKey(int $userId): string
    {
        return 'account_restore_otp:'.$userId;
    }

    private function restoreOtpCooldownCacheKey(int $userId): string
    {
        return 'account_restore_otp_cooldown:'.$userId;
    }

    private function restoredUserStatus(User $user): UserStatus
    {
        if ($user->role->isManufacturer() && $user->manufacture_status === UserManuFactureStatus::PENDING) {
            return UserStatus::PENDING;
        }

        return UserStatus::ACTIVE;
    }
}
