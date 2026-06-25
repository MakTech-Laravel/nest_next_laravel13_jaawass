<?php

namespace App\Services\Manufacturer;

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketMessageService;
use Illuminate\Support\Facades\DB;

class ManufacturerRegistrationTicketService
{
    public function __construct(
        private readonly TicketMessageService $ticketMessageService,
    ) {}

    public function createForManufacturer(
        User $manufacturer,
        User $admin,
        string $subject,
        string $message,
        TicketDepartmentType $department = TicketDepartmentType::Account,
        TicketStatus $status = TicketStatus::WaitingOnCustomer,
        bool $assignToAdmin = true,
    ): Ticket {
        return DB::transaction(function () use (
            $manufacturer,
            $admin,
            $subject,
            $message,
            $department,
            $status,
            $assignToAdmin,
        ): Ticket {
            $ticket = Ticket::query()->create([
                'user_id' => $manufacturer->id,
                'subject' => $subject,
                'department_type' => $department->value,
                'priority' => TicketPriority::Medium->value,
                'status' => $status->value,
                'assigned_to' => $assignToAdmin ? $admin->id : null,
            ]);

            $this->ticketMessageService->sendMessage(
                $ticket,
                $admin,
                $message,
            );

            return $ticket->load(['user', 'assignee']);
        });
    }
}
