<?php

namespace App\Http\Resources\Api\V1\Manufacturer;

use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogResource extends JsonResource
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
            $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'name' => $name,
            'file_size' => $this->file_size ? round($this->file_size / (1024 * 1024), 2).' MB' : '0 MB',
            'file_path' => storage_url($this->file_path),
            'total_downloads' => $this->total_downloads,
            'status' => $this->status,
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
        ];
    }
}
