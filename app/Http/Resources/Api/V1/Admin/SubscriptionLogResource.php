<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'manufacturer' => $this->whenLoaded('manufacturer', fn () => [
                'id' => $this->manufacturer->id,
                'name' => trim($this->manufacturer->first_name.' '.$this->manufacturer->last_name),
                'email' => $this->manufacturer->email,
            ]),
            'from_plan' => $this->whenLoaded('fromPlan', fn () => $this->fromPlan ? [
                'id' => $this->fromPlan->id,
                'name' => $this->fromPlan->name,
            ] : null),
            'to_plan' => $this->whenLoaded('toPlan', fn () => $this->toPlan ? [
                'id' => $this->toPlan->id,
                'name' => $this->toPlan->name,
            ] : null),
            'event_type' => $this->event_type,
            'paid_amount' => $this->paid_amount !== null ? (float) $this->paid_amount : null,
            'created_at' => $this->created_at,
        ];
    }
}
