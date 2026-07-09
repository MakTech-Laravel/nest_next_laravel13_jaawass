<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Auth\RevokePassportTokensAction;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\AccountPasswordReasonRequest;
use App\Http\Requests\Api\V1\Account\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Account\RestoreDeleteOtpRequest;
use App\Http\Requests\Api\V1\Account\RestoreDeleteVerifyRequest;
use App\Http\Resources\Api\V1\UserLoginHistoryResource;
use App\Services\Account\AccountRestoreService;
use App\Services\Auth\PasswordChangedNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountRestoreService $accountRestoreService,
    ) {}

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

    public function requestRestoreOtp(RestoreDeleteOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->accountRestoreService->requestOtp(
            $validated['email'],
            $validated['password'],
        );

        return sendResponse(
            status: $result->success,
            message: $result->message,
            data: $result->data,
            statusCode: $result->statusCode,
        );
    }

    public function verifyRestoreOtp(RestoreDeleteVerifyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->accountRestoreService->verifyOtp(
            $validated['email'],
            $validated['otp'],
        );

        return sendResponse(
            status: $result->success,
            message: $result->message,
            data: $result->data,
            statusCode: $result->statusCode,
        );
    }

    public function changePassword(ChangePasswordRequest $request, PasswordChangedNotificationService $passwordChangedNotificationService): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        $passwordChangedNotificationService->notify($user, $request);

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
}
