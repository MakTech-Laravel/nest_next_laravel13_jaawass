<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Api\V1\Admin\Users\DeactivateUserAction;
use App\Actions\Api\V1\Admin\Users\DeleteUserAction;
use App\Actions\Api\V1\Admin\Users\ReactivateUserAction;
use App\Actions\Api\V1\Admin\Users\SuspendUserAction;
use App\Actions\Api\V1\Admin\Users\UnsuspendUserAction;
use App\Actions\Api\V1\Admin\Users\UpdateManufactureStatusAction;
use App\Filters\Api\V1\Admin\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\DeactivateUserRequest;
use App\Http\Requests\Api\V1\Admin\DeleteUserRequest;
use App\Http\Requests\Api\V1\Admin\IndexUserRequest;
use App\Http\Requests\Api\V1\Admin\SuspendUserRequest;
use App\Http\Requests\Api\V1\Admin\UnsuspendUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateManufactureStatusRequest;
use App\Http\Resources\Api\V1\UserCollection;
use App\Http\Resources\Api\V1\UserLoginHistoryResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpStatus;

class UserController extends Controller
{
    public function __construct(
        private readonly DeactivateUserAction $deactivateUser,
        private readonly ReactivateUserAction $reactivateUser,
        private readonly SuspendUserAction $suspendUser,
        private readonly UnsuspendUserAction $unsuspendUser,
        private readonly UpdateManufactureStatusAction $updateManufactureStatus,
        private readonly DeleteUserAction $deleteUser,
    ) {}

    public function index(IndexUserRequest $request): JsonResponse
    {
        $users = UserFilter::apply(
            User::query()->with(['company']),
            $request,
        )->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('messages.users.fetched'),
            data: new UserCollection($users),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load($this->relationsForShow($user));

        return sendResponse(
            status: true,
            message: __('messages.users.found'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function deactivate(DeactivateUserRequest $request, User $user): JsonResponse
    {
        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('messages.users.cannot_deactivate_admin'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->deactivated_at !== null) {
            return sendResponse(
                status: false,
                message: __('messages.users.already_deactivated'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $updated = $this->deactivateUser->handle($user, $request->validated('reason'));

        return sendResponse(
            status: true,
            message: __('messages.users.deactivated'),
            data: new UserResource($updated),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function reactivate(User $user): JsonResponse
    {
        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('messages.users.cannot_reactivate_admin'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->deactivated_at === null) {
            return sendResponse(
                status: false,
                message: __('messages.users.not_deactivated'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $updated = $this->reactivateUser->handle($user);

        return sendResponse(
            status: true,
            message: __('messages.users.reactivated'),
            data: new UserResource($updated),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function suspend(SuspendUserRequest $request, User $user): JsonResponse
    {
        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('messages.users.cannot_suspend_admin'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->status?->isSuspended()) {
            return sendResponse(
                status: false,
                message: __('messages.users.already_suspended'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $updated = $this->suspendUser->handle($user);

        return sendResponse(
            status: true,
            message: __('messages.users.suspended'),
            data: new UserResource($updated),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function unsuspend(UnsuspendUserRequest $request, User $user): JsonResponse
    {
        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('messages.users.cannot_unsuspend_admin'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (! $user->status?->isSuspended()) {
            return sendResponse(
                status: false,
                message: __('messages.users.not_suspended'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->role->isManufacturer() && $user->manufacture_status?->isRejected()) {
            return sendResponse(
                status: false,
                message: __('messages.users.cannot_unsuspend_manufacturer_rejected'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $updated = $this->unsuspendUser->handle($user);

        return sendResponse(
            status: true,
            message: __('messages.users.unsuspended'),
            data: new UserResource($updated),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function updateManufactureStatus(UpdateManufactureStatusRequest $request, User $user): JsonResponse
    {
        if (! $user->role->isManufacturer()) {
            return sendResponse(
                status: false,
                message: __('messages.users.not_manufacturer'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $updated = $this->updateManufactureStatus->handle(
            $user,
            $request->resolvedStatus(),
            $request->validated('reason'),
        );

        return sendResponse(
            status: true,
            message: __('messages.users.manufacture_status_updated'),
            data: new UserResource($updated),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function loginHistories(Request $request, User $user): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $histories = $user->loginHistories()->paginate($perPage);

        return sendResponse(
            status: true,
            message: __('messages.users.login_histories_fetched'),
            data: UserLoginHistoryResource::collection($histories),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function destroy(DeleteUserRequest $request, User $user): JsonResponse
    {

        if ($user->role->isAdmin()) {
            return sendResponse(
                status: false,
                message: __('messages.users.cannot_delete_admin'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->deleted_at !== null) {
            return sendResponse(
                status: false,
                message: __('messages.users.already_deleted'),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $this->deleteUser->handle($user, $request->validated('reason'));

        return sendResponse(
            status: true,
            message: __('messages.users.deleted'),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    /* ------------------------------------------------------------------
    |                          Private Helpers
    | ------------------------------------------------------------------ */

    /**
     * Returns the correct eager-load relations for a single-user show response,
     * scoped to what each role actually exposes in UserResource.
     *
     * @return array<int, string>
     */
    private function relationsForShow(User $user): array
    {
        return match (true) {
            $user->role->isAdmin() => ['loginHistories'],
            $user->role->isBuyer() => ['company', 'loginHistories', 'preferredCurrency', 'reviewerReviews'],
            $user->role->isManufacturer() => ['company.industries', 'factoryImages', 'loginHistories', 'preferredCurrency', 'manufacturerReviews'],
            default => [],
        };
    }


    public function activate(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }
        $user->update([
            'status' => 'active',
            'deleted_at' => null,
            "deleted_reason" => null,
            'deactivated_at' => null,
            'deactivated_reason' => null,
        ]);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new UserResource($user->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
