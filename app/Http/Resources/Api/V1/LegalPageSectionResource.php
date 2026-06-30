<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalPageSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();

        ['title' => $title, 'content' => $content] = $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'section_key' => $this->section_key,
            'title' => $title,
            'content' => $content,
            'sort' => $this->sort,
        ];
    }
}
