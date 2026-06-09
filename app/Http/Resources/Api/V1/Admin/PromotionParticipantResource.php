<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Enums\PromotionUserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = PromotionUserStatus::from($this->pivot->status);

        return [
            'user_id' => $this->id,
            'supplier' => [
                'company_name' => $this->whenLoaded('company', fn () => $this->company?->company_name),
                'email' => $this->email,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
            ],
            'country' => $this->whenLoaded('company', fn () => $this->company?->country),
            'joined_at' => $this->pivot->participated_at,
            'status' => $status->value,
            'status_label' => $status->label(),
            'trial_ends_at' => $this->pivot->trial_ends_at,
        ];
    }
}
