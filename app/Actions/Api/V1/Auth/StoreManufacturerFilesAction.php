<?php

namespace App\Actions\Api\V1\Auth;

use App\Models\User;
use App\Models\UserFactoryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreManufacturerFilesAction
{
    public function handle(User $user, ?UploadedFile $businessLicenseFile, array $factoryImages = []): array
    {
        $businessLicensePath = null;

        if ($businessLicenseFile instanceof UploadedFile) {
            $businessLicensePath = $businessLicenseFile->store('manufacturer/business-licenses', 'public');
        }

        $savedFactoryImages = [];

        foreach ($factoryImages as $factoryImage) {
            if (! $factoryImage instanceof UploadedFile) {
                continue;
            }

            $storedPath = $factoryImage->store('manufacturer/factory-images', 'public');

            $savedFactoryImages[] = UserFactoryImage::query()->create([
                'user_id' => $user->id,
                'path' => $storedPath,
                'mime_type' => $factoryImage->getMimeType(),
                'extension' => $factoryImage->getClientOriginalExtension(),
                'original_name' => $factoryImage->getClientOriginalName(),
            ]);
        }

        return [
            'business_license_path' => $businessLicensePath,
            'factory_images' => $savedFactoryImages,
        ];
    }
}
