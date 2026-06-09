<?php

namespace App\Http\Resources\Api\V1\Buyer;

use App\Enums\UserManuFactureStatus;
use App\Http\Resources\Api\V1\UserInformationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class SupplierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();
        $company = $this->company;
        $companyName = $company?->company_name;

        if ($company !== null) {
            $companyName = $company->localizedData($locale)['company_name'] ?? $companyName;
        }

        $normalizedStatus = UserManuFactureStatus::normalizedForManufacturer($this->manufacture_status);

        return [
            'id' => $this->id,
            'company_name' => $companyName,
            'avatar_url' => $this->avatar_url,
            'location' => collect([
                $company?->city,
                $company?->country,
            ])->filter()->implode(', '),
            'manufacture_status' => $normalizedStatus->value,
            'manufacture_status_label' => $normalizedStatus->label(),
            'company' => $this->whenLoaded('company', function () use ($request): ?UserInformationResource {
                if ($this->company === null) {
                    return null;
                }

                return new UserInformationResource($this->company);
            }),
        ];
    }
}
