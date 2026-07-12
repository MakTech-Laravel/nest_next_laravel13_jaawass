<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\SubscriptionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubscriptionResource extends JsonResource
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
            'plan' => $this->whenLoaded('plan', fn () => new PlanResource($this->plan)),
            'billing_interval' => $this->billing_interval,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'auto_renew' => $this->auto_renew,
            'payment_method' => $this->payment_method,
            'has_reusable_payment_method' => filled($this->paypal_vault_id),
            'renew_attempts' => $this->renew_attempts,
            'last_renew_attempt_at' => $this->last_renew_attempt_at,
            'last_renewed_at' => $this->last_renewed_at,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'trial_ends_at' => $this->trial_ends_at,
            'is_active' => $this->isEntitlementActive(),
            'source' => $this->source?->value ?? $this->source,
            'promotion_id' => $this->promotion_id,
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'logs' => SubscriptionLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
