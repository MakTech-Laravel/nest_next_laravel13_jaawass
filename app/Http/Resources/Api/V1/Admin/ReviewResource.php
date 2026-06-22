<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'status' => $this->status?->value,
            'user_id' => $this->user_id,
            'reviewer_id' => $this->reviewer_id,
            'created_at' => TimezoneFormatter::format($this->created_at),
        ];
    }
}
