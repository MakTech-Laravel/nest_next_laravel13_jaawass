<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Product\ProductResource;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'quantity_unit' => $this->quantity_unit,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
            'notes' => $this->notes,
            'product' => $this->product === null
                ? null
                : new ProductResource($this->product),
        ];
    }
}
