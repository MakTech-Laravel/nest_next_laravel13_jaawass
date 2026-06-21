<?php

namespace App\Http\Resources\Api\V1\Manufacturer;

use App\Enums\AdditionalInformationRequestStatus;
use App\Http\Resources\Api\V1\ManufacturerAdditionalInformationResponseResource;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManufacturerReviewCenterAdditionalInformationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return [
            'id' => $this->id,
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
            'submit_url' => $this->status === AdditionalInformationRequestStatus::Pending
                ? "{$frontendUrl}/manufacturer-additional-information-request/{$this->token}"
                : null,
            'requested_by' => $this->whenLoaded('requestedBy', fn () => [
                'id' => $this->requestedBy->id,
                'name' => trim($this->requestedBy->first_name.' '.$this->requestedBy->last_name),
                'email' => $this->requestedBy->email,
            ]),
            'responses' => ManufacturerAdditionalInformationResponseResource::collection(
                $this->whenLoaded('responses')
            ),
        ];
    }
}
