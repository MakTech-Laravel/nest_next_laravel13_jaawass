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
        $this->resource->loadMissing([
            'translations',
            'product.translations',
            'product.category.translations',
        ]);

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
            'updated_at' => TimezoneFormatter::format($this->updated_at),
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
                'full_name' => trim("{$this->reviewer->first_name} {$this->reviewer->last_name}"),
                'company_name' => $this->reviewer->company?->company_name,
                'country' => $this->reviewer->company?->country,
            ]),
            'supplier' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'company_name' => $this->user->company?->company_name,
                'country' => $this->user->company?->country,
            ]),
            'product' => $this->whenLoaded('product', function () use ($locale) {
                if ($this->product === null) {
                    return null;
                }

                ['name' => $productName] = $this->product->localizedData($locale);
                $categoryName = null;

                if ($this->product->relationLoaded('category') && $this->product->category !== null) {
                    ['name' => $categoryName] = $this->product->category->localizedData($locale);
                }

                return [
                    'id' => $this->product->id,
                    'name' => $productName,
                    'category' => $categoryName,
                ];
            }),
            'order' => $this->whenLoaded('order', fn () => $this->order === null ? null : [
                'id' => $this->order->id,
                'total_amount' => $this->order->total_amount,
                'currency_code' => $this->order->currency_code,
                'status' => $this->order->status?->value,
                'status_label' => $this->order->status?->label(),
                'created_at' => TimezoneFormatter::format($this->order->created_at),
            ]),
        ];
    }
}
