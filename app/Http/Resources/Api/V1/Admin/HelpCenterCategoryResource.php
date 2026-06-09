<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelpCenterCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();

        ['name' => $name, 'description' => $description] = $this->resource->localizeData($locale);

        return [
            'id' => $this->id,
            'name' => $name,
            'slug' => $this->slug,
            'description' => $description,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'available_locales' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->pluck('locale')->sort()->values(),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
