<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
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
        ['question' => $question, 'answer' => $answer] =
            $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'question' => $question,
            'answer' => $answer,
            'clicks_count' => $this->clicks_count ?? 0,
            'category' => $this->whenLoaded('category', function () {
                return new FaqCategoryResource($this->category);
            }),
            'sort' => $this->sort,
        ];
    }
}
