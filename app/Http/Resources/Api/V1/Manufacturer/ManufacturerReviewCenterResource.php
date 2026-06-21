<?php

namespace App\Http\Resources\Api\V1\Manufacturer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManufacturerReviewCenterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->resource['user'],
            'verification' => $this->resource['verification'],
            'additional_information_requests' => ManufacturerReviewCenterAdditionalInformationResource::collection(
                $this->resource['additional_information_requests']
            ),
            'review_requests' => $this->resource['review_requests'],
        ];
    }
}
