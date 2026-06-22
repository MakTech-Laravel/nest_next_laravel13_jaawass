<?php

namespace App\Http\Resources\Api\V1\Admin;

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
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'status' => $this->status?->value,
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer === null ? null : [
                'id' => $this->reviewer->id,
                'first_name' => $this->reviewer->first_name,
                'last_name' => $this->reviewer->last_name,
                'full_name' => trim("{$this->reviewer->first_name} {$this->reviewer->last_name}"),
                'company_name' => $this->reviewer->company?->company_name,
                'country' => $this->reviewer->company?->country,
            ]),
            'supplier' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'company_name' => $this->user->company?->company_name,
                'country' => $this->user->company?->country,
            ]),
            'product' => $this->whenLoaded('product', fn () => $this->product === null ? null : [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'category' => $this->product->category?->name,
            ]),
            'order' => $this->whenLoaded('order', fn () => $this->order === null ? null : [
                'id' => $this->order->id,
                'total_amount' => $this->order->total_amount,
                'currency_code' => $this->order->currency_code,
                'status' => $this->order->status?->value,
                'created_at' => TimezoneFormatter::format($this->order->created_at),
            ]),
        ];
    }
}
