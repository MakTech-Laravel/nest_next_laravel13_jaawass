<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelpCenterArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();

        ['title' => $title, 'description' => $description] = $this->resource->localizeData($locale);

        return [
            'id' => $this->id,
            'help_center_category_id' => $this->help_center_category_id,
            'title' => $title,
            'description' => $description,
            'help_full' => $this->help_full,
            'not_help_full' => $this->not_help_full,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'views' => $this->views,
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category !== null,
                fn () => new HelpCenterCategoryResource($this->category),
            ),
            'steps' => HelpCenterArticleStepResource::collection($this->steps),
            'available_locales' => $this->translations->pluck('locale')->sort()->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
