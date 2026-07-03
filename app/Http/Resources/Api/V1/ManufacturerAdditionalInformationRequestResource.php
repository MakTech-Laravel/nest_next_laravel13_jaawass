<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AdditionalInformationRequestStatus;
use App\Enums\AdditionalInformationType;
use App\Enums\UserManuFactureStatus;
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
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        $data = [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'token' => $this->token,
            'message' => $this->message,
            'allowed_types' => $this->allowed_types,
            'allowed_type_labels' => collect($this->allowed_types ?? [])
                ->map(fn (string $type) => AdditionalInformationType::from($type)->label())
                ->values()
                ->all(),
            'reference_id' => sprintf('SN-MFR-%06d', $this->id),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'expires_at' => TimezoneFormatter::format($this->expires_at),
            'submitted_at' => TimezoneFormatter::format($this->submitted_at),
            'reviewed_at' => TimezoneFormatter::format($this->reviewed_at),
            'review_notes' => $this->review_notes,
            'created_at' => TimezoneFormatter::format($this->created_at),
            'response_count' => $this->whenCounted('responses'),
            'requested_by' => $this->whenLoaded('requestedBy', fn () => [
                'id' => $this->requestedBy->id,
                'name' => trim($this->requestedBy->first_name.' '.$this->requestedBy->last_name),
                'email' => $this->requestedBy->email,
            ]),
            'reviewed_by' => $this->whenLoaded('reviewedBy', fn () => [
                'id' => $this->reviewedBy->id,
                'name' => trim($this->reviewedBy->first_name.' '.$this->reviewedBy->last_name),
                'email' => $this->reviewedBy->email,
            ]),
            'manufacturer' => $this->whenLoaded('manufacturer', function () {
                $manufacturer = $this->manufacturer;
                $manufactureStatus = UserManuFactureStatus::normalizedForManufacturer($manufacturer->manufacture_status);

                return [
                    'id' => $manufacturer->id,
                    'name' => trim($manufacturer->first_name.' '.$manufacturer->last_name) ?: null,
                    'email' => $manufacturer->email,
                    'company_name' => $manufacturer->company?->company_name,
                    'manufacture_status' => $manufactureStatus->value,
                    'manufacture_status_label' => $manufactureStatus->label(),
                ];
            }),
            'responses' => ManufacturerAdditionalInformationResponseResource::collection(
                $this->whenLoaded('responses')
            ),
            'review_url' => $this->when(
                $request->user()?->role?->isAdmin(),
                fn () => $this->ticket_id !== null
                    ? "{$frontendUrl}/admin/customer-supports/tickets/{$this->ticket_id}"
                    : "{$frontendUrl}/admin/manufacturer-registrations?manufacturer={$this->user_id}"
            ),
            'submit_url' => $this->when(
                $request->user()?->role?->isManufacturer()
                    && $this->status === AdditionalInformationRequestStatus::Pending,
                fn () => "{$frontendUrl}/review?token={$this->token}"
            ),
        ];

        if (env('APP_ENV') === 'local') {
            $data['test_url'] = config('app.frontend_url').'/review?token='.$this->token;
        }

        return $data;

    }
}
