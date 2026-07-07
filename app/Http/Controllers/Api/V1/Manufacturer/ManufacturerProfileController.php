<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\Profile\UpdateManufacturerProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Models\UserFactoryImage;
use App\Services\Auth\PasswordChangedNotificationService;
use App\Services\Company\CompanySlugService;
use App\Services\Manufacturer\ManufacturerExportMarketService;
use App\Support\Manufacturer\ManufacturerProfileRelations;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = ManufacturerProfileRelations::load($request->user());

        return sendResponse(
            status: true,
            data: new UserResource($user),
            message: __('common.success'),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function update(UpdateManufacturerProfileRequest $request)
    {


        $user = $request->user();

        $validated = $request->validated();

        $remove_images = $validated['remove_images'] ?? [];

        if (!empty($remove_images) && $user->company) {

            $imagesToDelete = $user->company->factoryImages()->whereIn('id', $remove_images)->get();

            foreach ($imagesToDelete as $image) {

                if ($image->path && Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
            }

            $user->company->factoryImages()->whereIn('id', $remove_images)->delete();
        }



        unset($validated['remove_images']);

        $images = [];

        if (isset($validated['factory_images']) && !empty($validated['factory_images'])) {

            foreach ($validated['factory_images'] as $image) {

                if ($image instanceof UploadedFile) {

                    $mime = $image->getMimeType();
                    $original_name = $image->getClientOriginalName();
                    $original_extension = $image->getClientOriginalExtension();

                    $new_name = uniqid() . '.' . $original_extension;

                    Storage::disk('public')->put('factory_images/' . $new_name, $image->getContent());

                    $images[] = [
                        'path' => 'factory_images/' . $new_name,
                        'original_name' => $original_name,
                        'mime_type' => $mime,
                        'size' => $image->getSize(),
                    ];
                }
            }

            $user->factoryImages()->createMany($images);
        }

        unset($validated['factory_images']);

        if (isset($validated['company_logo']) && $validated['company_logo'] instanceof UploadedFile) {

            $name = uniqid() . '.' . $validated['company_logo']->getClientOriginalExtension();
            Storage::disk('public')->put('company_logos/' . $name, $validated['company_logo']->getContent());
            $validated['company_logo'] = 'company_logos/' . $name;
        } else {
            unset($validated['company_logo']);
        }



        $industries_id = $validated['industries_id'] ?? [];
        unset($validated['industries_id']);


        if (isset($validated['capabilities']) && !empty($validated['capabilities'])) {
            $validated['capabilities'] = json_encode($validated['capabilities']);
        }


        if (isset($validated['certifications']) && !empty($validated['certifications'])) {
            $validated['certifications'] = json_encode($validated['certifications']);
        }

        $profileExportMarkets = null;

        if (array_key_exists('export_markets', $validated)) {
            $profileExportMarkets = is_array($validated['export_markets'])
                ? $validated['export_markets']
                : [];
            $validated['export_markets'] = json_encode($profileExportMarkets);
        }

        if (isset($validated['language_spoken']) && !empty($validated['language_spoken'])) {
            $validated['language_spoken'] = json_encode($validated['language_spoken']);
        }

        if (isset($validated['payments_term']) && !empty($validated['payments_term'])) {
            $validated['payments_term'] = json_encode($validated['payments_term']);
        }

        $company = $user->company;

        if (!$company) {
            $user->company()->create($validated);

            // Get only translatable fields that were actually validated
            $translatableData = array_intersect_key(
                $validated,
                array_flip([
                    'company_name',
                    'company_type',
                    'company_established',
                    'company_size',
                    'revenue',
                    'country',
                    'city',
                    'street_address',
                    'phone',
                    'zip_code',
                    'short_description',
                    'long_description',
                    'notes'
                ])
            );

            $company = $user->load('company')->company;
            if (!empty($translatableData)) {
                $company->autoTranslate(
                    sourceData: $translatableData,
                    sourceLocale: $request->locale ?? null,
                );
            }
        } else {
            $company->update($validated);

            $company = $user->load('company')->company;

            $translatableChanged = array_intersect_key(
                [
                    'company_name' => $validated['company_name'] ?? null,
                    'company_type' => $validated['company_type'] ?? null,
                    'company_established' => $validated['company_established'] ?? null,
                    'company_size' => $validated['company_size'] ?? null,
                    'revenue' => $validated['revenue'] ?? null,
                    'country' => $validated['country'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'street_address' => $validated['street_address'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'zip_code' => $validated['zip_code'] ?? null,
                    'short_description' => $validated['short_description'] ?? null,
                    'long_description' => $validated['long_description'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ],
                array_flip($company->translatableFields())
            );

            if (! empty($translatableChanged)) {
                $company->autoTranslate(
                    sourceData: $translatableChanged,
                    sourceLocale: $request->locale ?? null,
                );
            }
        }

        app(CompanySlugService::class)->syncSlug($company, $validated['company_name'] ?? null);






        if (!empty($industries_id)) {
            $company->industries()->sync($industries_id);
        }

        if ($profileExportMarkets !== null) {
            app(ManufacturerExportMarketService::class)->syncFromProfileRegions($user, $profileExportMarkets);
        }

        ManufacturerProfileRelations::load($user);

        return sendResponse(
            status: true,
            message: __('common.updated'),
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

        if (!Hash::check($validated['current_password'], $user->password)) {
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

        ManufacturerProfileRelations::load($user);

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK
        );
    }

        public function toggleStatus(Request $request)
    {


        $user = $request->user();

        $isActive = $user->status->value;


        if ($isActive == 'active') {
            $user->update(
                [
                    'status' => 'deactivated',
                    'deactivated_at' => now(),
                    'deactivated_reason' => 'Account deactivated by self',

                    'deleted_at' => null,
                    'deleted_reason' => null,
                ]
            );
        } else {
            $user->update([
                'status' => 'active',
                'deactivated_at' => null,
                'deactivated_reason' => null,
                'deleted_at' => null,
                'deleted_reason' => null,
            ]);
        }

        ManufacturerProfileRelations::load($user);

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK
        );
    }


    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Prepare validation rules
        $validationRules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20|unique:companies,phone',
        ];

        // If company exists, exclude current company ID from uniqueness check
        if ($user->company) {
            $validationRules['phone'] = 'required|string|max:20|unique:companies,phone,' . $user->company->id;
        }

        $validated = $request->validate($validationRules);

        // Update user basic info
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

       $user->load('company');
        if ($user->company) {
            // Update company phone
            $user->company->update([
                'phone' => $validated['phone'],
            ]);
        }else{
            // Create company
            $user->company()->create([
                'phone' => $validated['phone'],
            ]);
        }

        

       
        ManufacturerProfileRelations::load($user);

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new UserResource($user),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
