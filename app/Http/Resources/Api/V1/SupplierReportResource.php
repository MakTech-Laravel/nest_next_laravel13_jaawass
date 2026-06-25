<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupplierReportPriority;
use App\Enums\SupplierReportReason;
use App\Enums\SupplierReportStatus;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reason = $this->reason instanceof SupplierReportReason
            ? $this->reason
            : SupplierReportReason::tryFrom((string) $this->reason);

        $status = $this->status instanceof SupplierReportStatus
            ? $this->status
            : SupplierReportStatus::tryFrom((string) $this->status);

        $priority = $this->priority instanceof SupplierReportPriority
            ? $this->priority
            : SupplierReportPriority::tryFrom((string) $this->priority);

        return [
            'id' => $this->id,
            'reason' => $reason?->value ?? $this->reason,
            'reason_label' => $reason?->label(),
            'details' => $this->details,
            'status' => $status?->value ?? $this->status,
            'status_label' => $status?->label(),
            'priority' => $priority?->value ?? $this->priority,
            'priority_label' => $priority?->label(),
            'source_page' => $this->source_page,
            'resolved_at' => TimezoneFormatter::format($this->resolved_at),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => trim($this->supplier->first_name.' '.$this->supplier->last_name) ?: null,
                'email' => $this->supplier->email,
                'company_name' => $this->supplier->company?->company_name,
            ]),
            'reporter' => $this->when($request->user()?->role?->value === 'admin', fn () => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'name' => trim($this->reporter->first_name.' '.$this->reporter->last_name) ?: null,
                'email' => $this->reporter->email,
            ])),
        ];
    }
}
