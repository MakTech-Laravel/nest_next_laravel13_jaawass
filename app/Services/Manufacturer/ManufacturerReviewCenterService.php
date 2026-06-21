<?php

namespace App\Services\Manufacturer;

use App\Enums\UserManuFactureStatus;
use App\Models\User;
use App\Support\Manufacturer\ManufacturerProfileRelations;
use App\Support\Time\TimezoneFormatter;

class ManufacturerReviewCenterService
{
    /**
     * @return array<string, mixed>
     */
    public function forManufacturer(User $manufacturer): array
    {
        $manufacturer->load([
            'company',
        ]);
        ManufacturerProfileRelations::load($manufacturer);

        $normalizedStatus = UserManuFactureStatus::normalizedForManufacturer($manufacturer->manufacture_status);

        return [
            'user' => [
                'id' => $manufacturer->id,
                'first_name' => $manufacturer->first_name,
                'last_name' => $manufacturer->last_name,
                'email' => $manufacturer->email,
                'company_name' => $manufacturer->company?->company_name,
            ],
            'verification' => [
                'manufacture_status' => $normalizedStatus->value,
                'manufacture_status_label' => $normalizedStatus->label(),
                'rejection_reason' => $normalizedStatus->isRejected()
                    ? $manufacturer->manufacture_status_reason
                    : null,
                'manufacture_status_at' => TimezoneFormatter::format($manufacturer->manufacture_status_at),
                'submitted_at' => TimezoneFormatter::format($manufacturer->manufacture_status_at ?? $manufacturer->created_at),
            ],
            'additional_information_requests' => $manufacturer->additionalInformationRequests,
            'review_requests' => [],
        ];
    }
}
