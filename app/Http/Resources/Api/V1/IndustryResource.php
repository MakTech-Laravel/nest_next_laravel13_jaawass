<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Admin\SubCategoryResource;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndustryResource extends JsonResource
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
        ['name' => $name, 'description' => $description] =
            $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'name' => $name,
            'description' => $description,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'icon_color' => $this->icon_color,
            'color' => $this->color,
            'title_color' => $this->title_color,
            'desc_color' => $this->desc_color,
            'btn_color' => $this->btn_color,
            'supplier_color' => $this->supplier_color,
            'featured' => $this->featured ?? false,
            'sort_order' => $this->sort_order,
            'sub_categories' => $this->whenLoaded('subCategories', function () {
                return SubCategoryResource::collection($this->subCategories);
            }),

            'supplier_count' => $this->resolveSupplierCount(),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
        ];
    }

    private function resolveSupplierCount(): int
    {
        if (isset($this->suppliers_count)) {
            return (int) $this->suppliers_count;
        }

        if ($this->relationLoaded('companies')) {
            return $this->companies->count();
        }

        return 0;
    }
}
