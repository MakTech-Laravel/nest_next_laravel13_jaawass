<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Resources\Api\V1\TicketMessageResource;
use App\Models\Ticket;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof TicketStatus
            ? $this->status
            : TicketStatus::tryFrom((string) $this->status);

        $priority = $this->priority instanceof TicketPriority
            ? $this->priority
            : TicketPriority::tryFrom((string) $this->priority);

        $departmentType = $this->department_type instanceof TicketDepartmentType
            ? $this->department_type
            : TicketDepartmentType::tryFrom((string) $this->department_type);

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'status' => $status?->value ?? $this->status,
            'priority' => $priority?->value ?? $this->priority,
            'department_type' => $departmentType?->value ?? $this->department_type,
            'assigned_to' => $this->assigned_to,
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'role' => $this->user->role,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee === null ? null : [
                'id' => $this->assignee->id,
                'first_name' => $this->assignee->first_name,
                'last_name' => $this->assignee->last_name,
                'email' => $this->assignee->email,
            ]),
            'messages' => TicketMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
