<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Enums\SupplierReportPriority;
use App\Enums\SupplierReportReason;
use App\Enums\SupplierReportStatus;
use App\Http\Resources\Api\V1\SupplierReportStatusLogResource;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierReportAdminResource extends JsonResource
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

        $supplierName = $this->supplier?->company?->company_name
            ?? trim(($this->supplier?->first_name ?? '').' '.($this->supplier?->last_name ?? ''))
            ?: null;

        return [
            'id' => $this->id,
            'subject' => $reason?->label(),
            'reason' => $reason?->value ?? $this->reason,
            'reason_label' => $reason?->label(),
            'details' => $this->details,
            'status' => $status?->value ?? $this->status,
            'status_label' => $status?->label(),
            'priority' => $priority?->value ?? $this->priority,
            'priority_label' => $priority?->label(),
            'source_page' => $this->source_page,
            'assigned_to' => $this->assigned_to,
            'resolved_at' => TimezoneFormatter::format($this->resolved_at),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter === null ? null : [
                'id' => $this->reporter->id,
                'name' => trim($this->reporter->first_name.' '.$this->reporter->last_name) ?: null,
                'email' => $this->reporter->email,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier === null ? null : [
                'id' => $this->supplier->id,
                'name' => trim($this->supplier->first_name.' '.$this->supplier->last_name) ?: null,
                'email' => $this->supplier->email,
                'company_name' => $supplierName,
            ]),
            'target' => $supplierName,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee === null ? null : [
                'id' => $this->assignee->id,
                'name' => trim($this->assignee->first_name.' '.$this->assignee->last_name) ?: null,
                'email' => $this->assignee->email,
            ]),
            'resolver' => $this->whenLoaded('resolver', fn () => $this->resolver === null ? null : [
                'id' => $this->resolver->id,
                'name' => trim($this->resolver->first_name.' '.$this->resolver->last_name) ?: null,
                'email' => $this->resolver->email,
            ]),
            'status_logs' => SupplierReportStatusLogResource::collection($this->whenLoaded('statusLogs')),
        ];
    }
}
