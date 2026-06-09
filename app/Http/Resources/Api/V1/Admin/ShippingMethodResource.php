<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();

        // Uses your existing LocaleTranslationResolver under the hood
        ['name' => $name] =
            $this->resource->localizedData($locale);
        return [
            'id' => $this->id,  
            'name' => $name,
            'status' => $this->status,
        ];
    }
}
