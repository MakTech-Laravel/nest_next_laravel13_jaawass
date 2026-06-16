<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class OrderProductSelectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'value' => $this->id,
            'label' => $this->name,
            'manufacturer_id' => $this->user_id,
            'manufacturer_name' => $this->user?->company?->company_name,
        ];
    }
}
