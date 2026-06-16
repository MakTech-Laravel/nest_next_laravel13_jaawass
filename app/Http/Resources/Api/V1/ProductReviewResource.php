<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Review;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ProductReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'created_at' => TimezoneFormatter::format($this->created_at),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer === null ? null : [
                'id' => $this->reviewer->id,
                'first_name' => $this->reviewer->first_name,
                'last_name' => $this->reviewer->last_name,
                'company_name' => $this->reviewer->company?->company_name,
                'country' => $this->reviewer->company?->country,
            ]),
            'order' => $this->whenLoaded('order', fn () => $this->order === null ? null : [
                'id' => $this->order->id,
                'total_amount' => $this->order->total_amount,
                'currency_code' => $this->order->currency_code,
                'status' => $this->order->status?->value,
            ]),
        ];
    }
}
