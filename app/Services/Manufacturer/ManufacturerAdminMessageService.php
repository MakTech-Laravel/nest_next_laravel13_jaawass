<?php

namespace App\Services\Manufacturer;

use App\Enums\MailTemplate;
use App\Enums\TicketDepartmentType;
use App\Enums\TicketStatus;
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
        $manufacturer->loadMissing('company');

        $subject = trim((string) $subject) !== ''
            ? trim((string) $subject)
            : __('manufacturer_admin_message.default_subject');

        $ticket = $this->ticketService->createForManufacturer(
            manufacturer: $manufacturer,
            admin: $admin,
            subject: $subject,
            message: $message,
            department: TicketDepartmentType::Account,
            status: TicketStatus::WaitingOnCustomer,
        );

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::ManufacturerAdminMessage,
            [
                'manufacturerName' => $this->displayName($manufacturer),
                'companyName' => $manufacturer->company?->company_name ?? config('app.name'),
                'adminName' => $this->displayName($admin),
                'adminMessage' => $message,
                'ticketSubject' => $subject,
                'ticketUrl' => $this->ticketUrl($ticket->id),
            ],
        );

        return $ticket;
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
