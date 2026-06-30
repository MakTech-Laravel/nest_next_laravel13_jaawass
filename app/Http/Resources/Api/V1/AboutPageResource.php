<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();

        return [
            'id' => $this->id,
            'enabled' => $this->enabled,
            'content' => $this->resource->localizedContent($locale),
            'available_locales' => $this->when(
                $this->relationLoaded('translations'),
                fn () => $this->translations->pluck('locale')->sort()->values()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
