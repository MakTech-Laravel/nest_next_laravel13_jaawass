<?php

namespace App\Actions\Api\V1\Auth;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Api\V1\Register\BuyerRegisterRequest;
use App\Http\Requests\Api\V1\Register\ManufacturerRegisterRequest;
use App\Enums\MailTemplate;
use App\Services\Mailing\MailingService;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterUserAction
{
    public function __construct(
        protected StoreManufacturerFilesAction $storeManufacturerFilesAction,
        protected IssuePersonalAccessTokenAction $issuePersonalAccessTokenAction,
        protected MailingService $mailingService,
    ) {}

    public function handle(Request $request): array
    {
        $payload = $request->all();
        $role = (string) ($payload['role'] ?? '');

        if ($role === UserRole::ADMIN->value) {
            throw ValidationException::withMessages([
                'role' => ['Admin accounts cannot be registered through this endpoint.'],
            ]);
        }

        $rules = match ($role) {
            UserRole::BUYER->value => app(BuyerRegisterRequest::class)->rules(),
            UserRole::MANUFACTURER->value => app(ManufacturerRegisterRequest::class)->rules(),
            default => throw ValidationException::withMessages([
                'role' => ['The selected role is invalid. Allowed roles are buyer and manufacturer.'],
            ]),
        };

        $validated = Validator::make($payload, $rules)->validate();

        $result = DB::transaction(function () use ($request, $validated, $role) {
            $userAttributes = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $role,
                'agreed_to_terms' => (bool) ($validated['terms_condition'] ?? false),
                'status' => UserStatus::ACTIVE,
            ];

            if ($role === UserRole::MANUFACTURER->value) {
                $userAttributes['manufacture_status'] = UserManuFactureStatus::PENDING;
                $userAttributes['manufacture_status_at'] = now();
                $userAttributes['manufacture_status_reason'] = null;
                $userAttributes['status'] = UserStatus::PENDING;
            }

            $user = User::query()->create($userAttributes);
            $this->mailingService->send($user->email, MailTemplate::Welcome, [
                'firstName' => trim($user->first_name) !== '' ? trim($user->first_name) : 'there',
            ]);

            $informationPayload = [
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'country' => $validated['country'],
            ];

            if ($role === UserRole::MANUFACTURER->value) {
                $fileData = $this->storeManufacturerFilesAction->handle(
                    user: $user,
                    businessLicenseFile: $request->file('bussiness_licence'),
                    factoryImages: $request->file('factory_images', [])
                );

                $informationPayload['bussiness_license'] = $fileData['business_license_path'];
                $informationPayload['city'] = $validated['city'];
                $informationPayload['company_website'] = $validated['company_website'] ?? null;
                $informationPayload['notes'] = $validated['notes'] ?? null;
            }

            Company::query()->create($informationPayload);

            if ($role === UserRole::MANUFACTURER->value) {
                return [
                    'user' => $user->fresh(['company', 'factoryImages']),
                    'manufacturer_pending' => true,
                ];
            }

            
            $accessToken = $this->issuePersonalAccessTokenAction->handle($user, $validated['device_name'] ?? null);

            return [
                'user' => $user->fresh(['company', 'factoryImages']),
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'manufacturer_pending' => false,
            ];
        });

        return $result;
    }
}
