<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Auth\RevokePassportTokensAction;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\AccountPasswordReasonRequest;
use App\Http\Requests\Api\V1\Account\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Account\RestoreDeleteOtpRequest;
use App\Http\Requests\Api\V1\Account\RestoreDeleteVerifyRequest;
use App\Http\Resources\Api\V1\UserLoginHistoryResource;
use App\Enums\MailTemplate;
use App\Services\Mailing\MailingService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AccountController extends Controller
{
    public function deactivate(AccountPasswordReasonRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('account.admin_cannot_modify'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        $user->forceFill([
            'deactivated_at' => now(),
            'deactivated_reason' => $request->validated('reason'),
            'status' => UserStatus::DEACTIVATED,
        ])->save();

        return sendResponse(status: true, message: __('account.deactivated'), data: null, statusCode: HttpStatus::HTTP_OK);
    }

    public function activate(AccountPasswordReasonRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('account.admin_cannot_modify'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        $user->forceFill([
            'deactivated_at' => null,
            'deactivated_reason' => null,
            'status' => UserStatus::ACTIVE,
        ])->save();

        return sendResponse(status: true, message: __('account.activated'), data: null, statusCode: HttpStatus::HTTP_OK);
    }

    public function requestDeletion(AccountPasswordReasonRequest $request, RevokePassportTokensAction $revokePassportTokensAction): JsonResponse
    {
        $user = $request->user();
        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('account.admin_cannot_modify'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        $user->forceFill([
            'deleted_at' => now(),
            'deleted_reason' => $request->validated('reason'),
            'is_permanently_deleted' => false,
            'status' => UserStatus::SCHEDULED_DELETION,
        ])->save();

        $revokePassportTokensAction->handle($user);

        $days = config('account.deletion_grace_days');

        return sendResponse(
            status: true,
            message: __('account.deletion_scheduled', ['days' => $days]),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function requestRestoreOtp(RestoreDeleteOtpRequest $request, MailingService $mailingService): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return sendResponse(status: false, message: __('auth.invalid_credentials'), data: null, statusCode: HttpStatus::HTTP_UNAUTHORIZED);
        }

        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('account.admin_cannot_modify'),
                // message: __('Invalid credentials.'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
                // statusCode: HttpStatus::HTTP_UNAUTHORIZED
            );
        }

        if ($user->deleted_at === null) {
            return sendResponse(status: false, message: __('account.no_scheduled_deletion'), data: null, statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->isAwaitingPermanentDeletionPurge()) {
            return sendResponse(status: false, message: __('account.deletion_processing'), data: null, statusCode: HttpStatus::HTTP_FORBIDDEN);
        }

        $cooldownKey = $this->restoreOtpCooldownCacheKey($user->id);

        if (Cache::has($cooldownKey)) {
            $availableAtTs = (int) Cache::get($cooldownKey);
            $retryAfterSeconds = max(0, $availableAtTs - now()->timestamp);
            $availableAt = Carbon::createFromTimestamp($availableAtTs);

            return sendResponse(
                status: false,
                message: __('account.restore_otp_resend_wait'),
                data: [
                    'retry_after_seconds' => $retryAfterSeconds,
                    'available_at' => $availableAt->toIso8601String(),
                ],
                statusCode: HttpStatus::HTTP_TOO_MANY_REQUESTS
            );
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->restoreOtpCacheKey($user->id);
        Cache::put($cacheKey, Hash::make($otp), now()->addMinutes(config('account.restore_otp_ttl_minutes')));

        $mailingService->send($user->email, MailTemplate::AccountRestoreOtp, ['otp' => $otp]);

        $resendSeconds = config('account.restore_otp_resend_seconds');
        $availableAt = now()->addSeconds($resendSeconds);
        Cache::put($cooldownKey, $availableAt->timestamp, $resendSeconds);

        return sendResponse(status: true, message: __('account.restore_otp_sent'), data: null, statusCode: HttpStatus::HTTP_OK);
    }

    public function verifyRestoreOtp(RestoreDeleteVerifyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return sendResponse(status: false, message: __('account.restore_invalid_otp'), data: null, statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('account.admin_cannot_modify'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        $cacheKey = $this->restoreOtpCacheKey($user->id);
        $hash = Cache::get($cacheKey);

        if (! is_string($hash) || ! Hash::check($validated['otp'], $hash)) {
            return sendResponse(status: false, message: __('account.restore_invalid_otp'), data: null, statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY);
        }

        Cache::forget($cacheKey);
        Cache::forget($this->restoreOtpCooldownCacheKey($user->id));

        $user->forceFill([
            'deleted_at' => null,
            'deleted_reason' => null,
            'status' => $this->restoredUserStatus($user),
        ])->save();

        return sendResponse(status: true, message: __('account.restore_success'), data: null, statusCode: HttpStatus::HTTP_OK);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return sendResponse(status: true, message: __('account.password_changed'), data: null, statusCode: HttpStatus::HTTP_OK);
    }
    // public function changePassword(ChangePasswordRequest $request, RevokePassportTokensAction $revokePassportTokensAction): JsonResponse
    // {
    //     $user = $request->user();

    //     $user->forceFill([
    //         'password' => $request->validated('password'),
    //     ])->save();

    //     $revokePassportTokensAction->handle($user);

    //     return sendResponse(status: true, message: __('account.password_changed'), data: null, statusCode: HttpStatus::HTTP_OK);
    // }

    public function loginHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = $user->loginHistories()
            ->orderByDesc('logged_in_at')
            ->paginate($perPage);

        return sendResponse(
            status: true,
            message: __('api.login_history'),
            data: UserLoginHistoryResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    protected function restoreOtpCacheKey(int $userId): string
    {
        return 'account_restore_otp:'.$userId;
    }

    protected function restoreOtpCooldownCacheKey(int $userId): string
    {
        return 'account_restore_otp_cooldown:'.$userId;
    }

    protected function restoredUserStatus(User $user): UserStatus
    {
        if ($user->role->isManufacturer() && $user->manufacture_status === UserManuFactureStatus::PENDING) {
            return UserStatus::PENDING;
        }

        return UserStatus::ACTIVE;
    }
}
