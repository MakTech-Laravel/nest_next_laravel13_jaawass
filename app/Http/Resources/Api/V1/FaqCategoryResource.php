<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Prefer explicit ?locale=, otherwise use middleware-resolved locale.
        $locale = $request->query('locale') ?? app()->getLocale();

        // Uses your existing LocaleTranslationResolver under the hood
        ['name' => $name] =
           $this->resource->localizedName($locale);

        return [
            'id' => $this->id,
            'name' => $name,
            'slug' => $this->slug,
            'sort' => $this->sort,
            'faqs' => $this->whenLoaded('faqs', function () {
                return $this->faqs->map(function ($faq) {
                    return new FaqResource($faq);
                });
            }),
            'available_locales' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->pluck('locale')->sort()->values()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
