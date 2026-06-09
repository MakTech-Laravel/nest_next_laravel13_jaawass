<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\IndustryResource;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Prefer explicit ?locale=, otherwise use middleware-resolved locale.
        $locale = $request->query('locale') ?? app()->getLocale();

        ['name' => $name, 'description' => $description] =
            $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'industry_id' => $this->industry_id,
            'name' => $name,
            'slug' => $this->slug,
            'description' => $description,
                'icon' => $this->icon,
                'icon_color' => $this->icon_color,
            'tags' => $this->tags ?? [],
            'sort_order' => $this->sort_order,
            'industry' => $this->whenLoaded('category', function () {
                return new IndustryResource($this->category);
            }),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
        ];
    }
}
