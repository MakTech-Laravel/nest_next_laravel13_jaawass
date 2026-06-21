<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AdditionalInformationRequestStatus;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManufacturerAdditionalInformationRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data =  [
            'id' => $this->id,
            'token' => $this->when($request->user()?->role?->isAdmin(), $this->token),
            'message' => $this->message,
            'allowed_types' => $this->allowed_types,
            'allowed_type_labels' => collect($this->allowed_types ?? [])
                ->map(fn (string $type) => \App\Enums\AdditionalInformationType::from($type)->label())
                ->values()
                ->all(),
         
            
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'expires_at' => TimezoneFormatter::format($this->expires_at),
            'submitted_at' => TimezoneFormatter::format($this->submitted_at),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'requested_by' => $this->whenLoaded('requestedBy', fn () => [
                'id' => $this->requestedBy->id,
                'name' => trim($this->requestedBy->first_name.' '.$this->requestedBy->last_name),
                'email' => $this->requestedBy->email,
            ]),
            'responses' => ManufacturerAdditionalInformationResponseResource::collection(
                $this->whenLoaded('responses')
            ),
            'submit_url' => $this->when(
                $request->user()?->role?->isManufacturer()
                    && $this->status === AdditionalInformationRequestStatus::Pending,
                fn () => rtrim((string) config('app.frontend_url'), '/')
                    ."/review?token={$this->token}"
            ),
        ];

        if(env('APP_ENV') === 'local') {
           $data['test_url'] = config('app.frontend_url').'/manufacturer-additional-information-request/'.$this->token;
        }

        return $data;


    }
}
