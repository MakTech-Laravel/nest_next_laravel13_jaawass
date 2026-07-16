<?php

namespace App\Services\Manufacturer;

use App\Enums\MailTemplate;
use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Mailing\MailingService;

class ManufacturerAdminMessageService
{
    public function __construct(
        private readonly ManufacturerRegistrationTicketService $ticketService,
        private readonly MailingService $mailingService,
    ) {}

    public function sendMessage(User $manufacturer, User $admin, string $message, ?string $subject = null): Ticket
    {
        $subject = trim((string) $subject) !== ''
            ? trim((string) $subject)
            : __('manufacturer_admin_message.default_subject');

        return $this->createSupportTicket(
            manufacturer: $manufacturer,
            admin: $admin,
            subject: $subject,
            message: $message,
        );
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function createSupportTicket(
        User $manufacturer,
        User $admin,
        string $subject,
        string $message,
        TicketDepartmentType $department = TicketDepartmentType::Account,
        TicketPriority $priority = TicketPriority::Medium,
        TicketStatus $status = TicketStatus::WaitingOnCustomer,
        array $attachments = [],
    ): Ticket {
        $manufacturer->loadMissing('company');

        $ticket = $this->ticketService->createForManufacturer(
            manufacturer: $manufacturer,
            admin: $admin,
            subject: $subject,
            message: $message,
            department: $department,
            status: $status,
            priority: $priority,
            attachments: $attachments,
        );

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::ManufacturerAdminMessage,
            [
                'name' => $this->displayName($manufacturer),
                'admin' => $this->displayName($admin),
                'company' => $manufacturer->company?->company_name ?? config('app.name'),
                'ticketSubject' => $subject,
                'messageBody' => nl2br(e($message)),
                'ctaUrl' => $this->ticketUrl($ticket->id),
                'referenceId' => 'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
            ],
        );

        $this->dispatchInAppNotification($ticket, $manufacturer, $admin, $subject, $message);

        return $ticket;
    }

    private function dispatchInAppNotification(
        Ticket $ticket,
        User $manufacturer,
        User $admin,
        string $subject,
        string $message,
    ): void {
        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $manufacturer->id,
            type: 'support.ticket.created',
            title: __('manufacturer_support_ticket.notification_title'),
            body: __('manufacturer_support_ticket.notification_body', [
                'adminName' => $this->displayName($admin),
                'subject' => $subject,
            ]),
            data: [
                'category' => 'support',
                'ticket_id' => $ticket->id,
                'subject' => $subject,
                'department_type' => $ticket->department_type instanceof \BackedEnum
                    ? $ticket->department_type->value
                    : (string) $ticket->department_type,
                'priority' => $ticket->priority instanceof \BackedEnum
                    ? $ticket->priority->value
                    : (string) $ticket->priority,
            ],
            actionUrl: $this->ticketUrl($ticket->id),
            senderId: $admin->id,
        );
    }

    private function displayName(User $user): string
    {
        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : 'there';
    }

    private function ticketUrl(int $ticketId): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $frontendUrl.'/dashboard/manufacturer/support-tickets/'.$ticketId;
    }
}
