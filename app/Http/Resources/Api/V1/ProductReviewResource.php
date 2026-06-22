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
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('translations');

        $locale = $request->query('locale') ?? app()->getLocale();

        ['title' => $title, 'comment' => $comment] = $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'locale' => $locale,
            'rating' => $this->rating,
            'title' => $title,
            'comment' => $comment,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'available_locales' => $this->translations->pluck('locale')->sort()->values(),
            'translations' => $this->translations
                ->sortBy('locale')
                ->values()
                ->map(fn ($translation): array => [
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'comment' => $translation->comment,
                ]),
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
                'status_label' => $this->order->status?->label(),
            ]),
        ];
    }
}
