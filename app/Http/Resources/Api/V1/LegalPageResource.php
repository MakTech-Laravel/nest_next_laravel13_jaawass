<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();

        ['title' => $title, 'last_updated_label' => $lastUpdatedLabel] = $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $title,
            'last_updated' => $lastUpdatedLabel,
            'enabled' => $this->enabled,
            'sort' => $this->sort,
            'sections' => LegalPageSectionResource::collection(
                $this->whenLoaded('sections')
            ),
            'available_locales' => $this->when(
                $this->relationLoaded('translations'),
                fn () => $this->translations->pluck('locale')->sort()->values()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
