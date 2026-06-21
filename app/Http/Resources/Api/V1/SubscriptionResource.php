<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Admin\PlanResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $daysRemaining = null;

        if ($this->ends_at !== null && $this->ends_at->isFuture()) {
            $daysRemaining = (int) now()->diffInDays($this->ends_at, false);
        }

        return [
            'id' => $this->id,
            'manufacturer' => $this->whenLoaded('manufacturer', function () {
                return new UserResource($this->manufacturer);
            }),
            'plan' => $this->whenLoaded('plan', function () {
                return new PlanResource($this->plan);
            }),

            'billing_interval' => $this->billing_interval,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'trial_ends_at' => $this->trial_ends_at,
            'auto_renew' => $this->auto_renew,
            'is_active' => $this->isEntitlementActive(),
            'source' => $this->source?->value ?? $this->source,
            'promotion_id' => $this->promotion_id,
            'days_remaining' => $daysRemaining,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
