<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupplierReportStatus;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierReportStatusLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fromStatus = $this->from_status instanceof SupplierReportStatus
            ? $this->from_status
            : SupplierReportStatus::tryFrom((string) $this->from_status);

        $toStatus = $this->to_status instanceof SupplierReportStatus
            ? $this->to_status
            : SupplierReportStatus::tryFrom((string) $this->to_status);

        return [
            'id' => $this->id,
            'from_status' => $fromStatus?->value ?? $this->from_status,
            'from_status_label' => $fromStatus?->label(),
            'to_status' => $toStatus?->value ?? $this->to_status,
            'to_status_label' => $toStatus?->label(),
            'message' => $this->message,
            'created_at' => TimezoneFormatter::format($this->created_at),
            'admin' => $this->whenLoaded('admin', fn () => $this->admin === null ? null : [
                'id' => $this->admin->id,
                'name' => trim($this->admin->first_name.' '.$this->admin->last_name) ?: null,
                'email' => $this->admin->email,
            ]),
        ];
    }
}
