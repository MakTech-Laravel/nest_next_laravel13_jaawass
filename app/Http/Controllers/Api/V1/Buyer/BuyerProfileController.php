<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Services\Auth\PasswordChangedNotificationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Buyer\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class BuyerProfileController extends Controller
{
    //

    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }


        $user->load(['loginHistories', 'preferredCurrency','company']);
        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new UserResource($request->user()),
            statusCode: HttpStatus::HTTP_OK
        );
    }


    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
        }

        $validated = $request->validated();

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        $company = $user->company;

        if ($company) {
            $company->update([
                'company_name' => $validated['company_name'],
                'phone' => $validated['phone'],
            ]);
        } else {
            $user->company()->create([
                'company_name' => $validated['company_name'],
                'phone' => $validated['phone'],
            ]);
        }


        $user->load(['loginHistories', 'preferredCurrency', 'company']);
        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function changePassword(Request $request, PasswordChangedNotificationService $passwordChangedNotificationService)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = $request->user();


       if(!Hash::check($validated['current_password'], $user->password)) {
            return sendResponse(
                status: false,
                message: __('validation.current_password'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $passwordChangedNotificationService->notify($user, $request);

        return sendResponse(
            status: true,
            message: __('common.password_changed'),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }


        public function toggleStatus(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
        }

        // Toggle the is_active status
       
        $current_status = $user->status ; 
        
        if($current_status == 'active') {
            $user->update([
                'status' => 'deactive',
                'deactivated_at' => now(),
                'deactivated_reason' => "Buyer deactivated account by itself",

                'deleted_at' => null,
                'deleted_reason' => null,
            ]);
        } else {
            $user->update([
                'status' => 'active',
                'deactivated_at' => null,
                'deactivated_reason' => null,

                'deleted_at' => null,
                'deleted_reason' => null,
            ]);
        }
        
        return sendResponse(
            status: true,
            message: __('common.status_toggled'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->update([
            'status' => 'scheduled_deletion',
            'deleted_at' => now(),
            'deleted_reason' => "Buyer deleted account by itself",

            'deactivated_at' => null,
            'deactivated_reason' => null,
        ]);

        return sendResponse(
            status: true,
            message: __('common.deleted'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function updateNotificationPreferences(Request $request)
    {
        $validated = $request->validate([
            'quote_notification' => 'nullable|boolean',
            'message_notification' => 'nullable|boolean',
            'supplier_update' => 'nullable|boolean',
            'weekly_digest' => 'nullable|boolean',
            'marketing_promotion' => 'nullable|boolean',
        ]);
        
        $user = $request->user();

        $user->update($validated);

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
