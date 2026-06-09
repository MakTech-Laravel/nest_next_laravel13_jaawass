<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAvailableOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $locale = $request->query('locale') ?? app()->getLocale();

        ['customization_detail' => $customization_detail] =
            $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'customization_detail' => $customization_detail,
        ];
    }
}
