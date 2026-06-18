<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'payment_method' => $this->payment_method,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'subscription_id' => $this->subscription_id,
            'manufacturer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => trim($this->user->first_name.' '.$this->user->last_name),
                'email' => $this->user->email,
            ]),
            'plan' => $this->when(
                $this->relationLoaded('source') && $this->source !== null,
                fn () => [
                    'id' => $this->source->id,
                    'name' => $this->source->name,
                ],
            ),
            'created_at' => $this->created_at,
        ];
    }
}
