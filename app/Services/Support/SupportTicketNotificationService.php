<?php

namespace App\Services\Support;

use App\Enums\MailTemplate;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class SupportTicketNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notifyCreated(Ticket $ticket, User $creator, ?string $message = null): void
    {
        $ticket->loadMissing(['user', 'assignee']);
        $subject = (string) $ticket->subject;
        $preview = $this->messagePreview($message);
        $userUrl = $this->userTicketUrl($ticket);
        $adminUrl = $this->adminTicketUrl($ticket);
        $ticketNumber = 'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT);
        $creatorName = MailNotificationHelper::displayName($creator);
        $creatorRole = $creator->role instanceof UserRole
            ? $creator->role
            : UserRole::tryFrom((string) $creator->role);
        $creatorType = match ($creatorRole) {
            UserRole::MANUFACTURER => 'Manufacturer',
            UserRole::BUYER => 'Buyer',
            UserRole::ADMIN => 'Admin',
            default => 'User',
        };

        MailNotificationHelper::sendIfEmail($creator, function (string $email) use ($creator, $subject, $preview, $userUrl, $ticketNumber): void {
            $this->mailingService->send($email, MailTemplate::SupportTicketCreated, $this->mailData(
                'mail.support_ticket_created',
                ['name' => MailNotificationHelper::displayName($creator), 'subject' => $subject],
                $preview,
                $userUrl,
                __('mail.support_ticket_created.cta'),
                $ticketNumber,
                [
                    'ticketNumber' => $ticketNumber,
                    'ticketSubject' => $subject,
                    'messageBodyPlain' => $preview,
                ],
            ));
        });

        $this->dispatchInApp(
            $creator,
            null,
            'support.ticket.created',
            __('mail.support_ticket_created.notification_title'),
            __('mail.support_ticket_created.notification_body', ['subject' => $subject]),
            ['ticket_id' => $ticket->id],
            $userUrl,
        );

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use (
                $creator,
                $creatorName,
                $creatorType,
                $subject,
                $preview,
                $adminUrl,
                $ticket,
                $ticketNumber,
            ): void {
                $this->mailingService->send($email, MailTemplate::SupportTicketCreatedAdmin, $this->mailData(
                    'mail.support_ticket_created_admin',
                    [
                        'user' => $creatorName,
                        'subject' => $subject,
                    ],
                    $preview,
                    $adminUrl,
                    __('mail.support_ticket_created_admin.cta'),
                    $ticketNumber,
                    [
                        'ticketNumber' => $ticketNumber,
                        'ticketSubject' => $subject,
                        'creatorName' => $creatorName,
                        'creatorEmail' => (string) ($creator->email ?? ''),
                        'creatorType' => $creatorType,
                        'creatorInitials' => MailNotificationHelper::initials($creatorName),
                        'submittedAt' => $ticket->created_at?->format('M j · g:i A') ?? '',
                        'messageBodyPlain' => $preview,
                    ],
                ));
            });

            $this->dispatchInApp(
                $admin,
                $creator,
                'support.ticket.created.admin',
                __('mail.support_ticket_created_admin.notification_title'),
                __('mail.support_ticket_created_admin.notification_body', [
                    'user' => $creatorName,
                    'subject' => $subject,
                ]),
                ['ticket_id' => $ticket->id],
                $adminUrl,
                $creator->id,
            );
        }
    }

    public function notifyReply(Ticket $ticket, User $sender, ?string $message): void
    {
        $ticket->loadMissing(['user', 'assignee']);
        $subject = (string) $ticket->subject;
        $preview = $this->messagePreview($message);
        $ticketNumber = 'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT);
        $senderName = MailNotificationHelper::displayName($sender);
        $senderRole = $sender->role instanceof UserRole
            ? $sender->role
            : UserRole::tryFrom((string) $sender->role);
        $senderType = match ($senderRole) {
            UserRole::MANUFACTURER => 'Manufacturer',
            UserRole::BUYER => 'Buyer',
            UserRole::ADMIN => 'Admin',
            default => 'User',
        };

        if ($senderRole === UserRole::ADMIN) {
            $this->notifyUserAboutAdminReply(
                $ticket,
                $sender,
                $senderName,
                $senderType,
                $subject,
                $preview,
                $ticketNumber,
            );

            return;
        }

        $this->notifyAdminsAboutUserReply(
            $ticket,
            $sender,
            $senderName,
            $senderType,
            $subject,
            $preview,
            $ticketNumber,
        );

        $this->sendUserReplyAcknowledgement($ticket, $sender, $subject, $preview, $ticketNumber);
    }

    public function notifyStatusChanged(
        Ticket $ticket,
        TicketStatus $status,
        ?User $actor = null,
        ?string $message = null,
    ): void {
        if (! in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            return;
        }

        $ticket->loadMissing(['user']);
        $owner = $ticket->user;

        if ($owner === null) {
            return;
        }

        $subject = (string) $ticket->subject;
        $statusLabel = $status->label();
        $url = $this->userTicketUrl($ticket, $owner);
        $preview = $this->messagePreview($message);

        MailNotificationHelper::sendIfEmail($owner, function (string $email) use ($owner, $subject, $statusLabel, $url, $ticket, $preview): void {
            $ticketNumber = 'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT);

            $this->mailingService->send($email, MailTemplate::SupportTicketResolved, [
                'name' => MailNotificationHelper::displayName($owner),
                'subject' => $subject,
                'status' => $statusLabel,
                'ctaUrl' => $url,
                'ctaLabel' => __('mail.support_ticket_resolved.cta'),
                'referenceId' => $ticketNumber,
                'ticketNumber' => $ticketNumber,
                'ticketSubject' => $subject,
                'messageBodyPlain' => $preview,
            ]);
        });

        $this->dispatchInApp(
            $owner,
            $actor,
            'support.ticket.'.$status->value,
            __('mail.support_ticket_resolved.notification_title'),
            __('mail.support_ticket_resolved.notification_body', [
                'subject' => $subject,
                'status' => $statusLabel,
            ]),
            ['ticket_id' => $ticket->id, 'status' => $status->value],
            $url,
            $actor?->id,
        );
    }

    private function notifyUserAboutAdminReply(
        Ticket $ticket,
        User $sender,
        string $senderName,
        string $senderType,
        string $subject,
        ?string $preview,
        string $ticketNumber,
    ): void {
        $recipient = $ticket->user;

        if ($recipient === null || (int) $recipient->id === (int) $sender->id) {
            return;
        }

        $url = $this->userTicketUrl($ticket, $recipient);

        MailNotificationHelper::sendIfEmail($recipient, function (string $email) use (
            $recipient,
            $sender,
            $senderName,
            $senderType,
            $subject,
            $preview,
            $url,
            $ticketNumber,
        ): void {
            $this->mailingService->send($email, MailTemplate::SupportTicketReply, [
                'name' => MailNotificationHelper::displayName($recipient),
                'senderName' => $senderName,
                'sender' => $senderName,
                'subject' => $subject,
                'messageBody' => $preview ? nl2br(e($preview)) : null,
                'messageBodyPlain' => $preview,
                'ctaUrl' => $url,
                'ctaLabel' => __('mail.support_ticket_reply.cta'),
                'referenceId' => $ticketNumber,
                'ticketNumber' => $ticketNumber,
                'ticketSubject' => $subject,
                'senderType' => $senderType,
                'senderEmail' => (string) ($sender->email ?? ''),
                'senderInitials' => MailNotificationHelper::initials($senderName),
                'repliedAt' => now()->format('M j · g:i A'),
            ]);
        });

        $this->dispatchInApp(
            $recipient,
            $sender,
            'support.ticket.reply',
            __('mail.support_ticket_reply.notification_title'),
            __('mail.support_ticket_reply.notification_body', [
                'sender' => $senderName,
                'subject' => $subject,
            ]),
            ['ticket_id' => $ticket->id],
            $url,
            $sender->id,
        );
    }

    private function notifyAdminsAboutUserReply(
        Ticket $ticket,
        User $sender,
        string $senderName,
        string $senderType,
        string $subject,
        ?string $preview,
        string $ticketNumber,
    ): void {
        $url = $this->adminTicketUrl($ticket);

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use (
                $admin,
                $sender,
                $senderName,
                $senderType,
                $subject,
                $preview,
                $url,
                $ticketNumber,
            ): void {
                $this->mailingService->send($email, MailTemplate::SupportTicketReplyAdmin, [
                    'name' => MailNotificationHelper::displayName($admin),
                    'senderName' => $senderName,
                    'sender' => $senderName,
                    'subject' => $subject,
                    'messageBody' => $preview ? nl2br(e($preview)) : null,
                    'messageBodyPlain' => $preview,
                    'ctaUrl' => $url,
                    'ctaLabel' => __('mail.support_ticket_reply_admin.cta'),
                    'referenceId' => $ticketNumber,
                    'ticketNumber' => $ticketNumber,
                    'ticketSubject' => $subject,
                    'senderType' => $senderType,
                    'senderEmail' => (string) ($sender->email ?? ''),
                    'senderInitials' => MailNotificationHelper::initials($senderName),
                    'repliedAt' => now()->format('M j · g:i A'),
                ]);
            });

            $this->dispatchInApp(
                $admin,
                $sender,
                'support.ticket.reply.admin',
                __('mail.support_ticket_reply_admin.notification_title'),
                __('mail.support_ticket_reply_admin.notification_body', [
                    'sender' => $senderName,
                    'subject' => $subject,
                ]),
                ['ticket_id' => $ticket->id],
                $url,
                $sender->id,
            );
        }
    }

    private function sendUserReplyAcknowledgement(
        Ticket $ticket,
        User $sender,
        string $subject,
        ?string $preview,
        string $ticketNumber,
    ): void {
        $url = $this->userTicketUrl($ticket, $sender);

        MailNotificationHelper::sendIfEmail($sender, function (string $email) use (
            $sender,
            $subject,
            $preview,
            $url,
            $ticketNumber,
        ): void {
            $this->mailingService->send($email, MailTemplate::SupportTicketReplyReceived, [
                'name' => MailNotificationHelper::displayName($sender),
                'subject' => $subject,
                'messageBodyPlain' => $preview,
                'ctaUrl' => $url,
                'ctaLabel' => __('mail.support_ticket_reply_received.cta'),
                'referenceId' => $ticketNumber,
                'ticketNumber' => $ticketNumber,
                'ticketSubject' => $subject,
                'receivedAt' => now()->format('M j · g:i A'),
            ]);
        });
    }

    private function messagePreview(?string $message): ?string
    {
        $trimmed = trim((string) $message);

        if ($trimmed === '') {
            return null;
        }

        return mb_strlen($trimmed) > 280 ? mb_substr($trimmed, 0, 277).'...' : $trimmed;
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function mailData(
        string $prefix,
        array $replacements,
        ?string $messageBody,
        string $ctaUrl,
        string $ctaLabel,
        string $referenceId,
        array $extra = [],
    ): array {
        return [
            'preheader' => __($prefix.'.preheader', $replacements),
            'headerEyebrow' => __('mail.layout.default_eyebrow'),
            'headerTitle' => __($prefix.'.header_title', $replacements),
            'headerSubtitle' => __($prefix.'.header_subtitle', $replacements),
            'intro' => __($prefix.'.intro', $replacements),
            'messageHeading' => $messageBody ? __($prefix.'.message_heading', $replacements) : null,
            'messageBody' => $messageBody ? nl2br(e($messageBody)) : null,
            'messageBodyPlain' => $messageBody,
            'ctaUrl' => $ctaUrl,
            'ctaLabel' => $ctaLabel,
            'referenceId' => $referenceId,
            'ticketNumber' => $referenceId,
            'ticketSubject' => $replacements['subject'] ?? '',
            'footerNote' => __($prefix.'.footer', $replacements),
            ...$extra,
        ];
    }

    private function userTicketUrl(Ticket $ticket, ?User $user = null): string
    {
        $role = $user?->role?->value ?? $ticket->user?->role?->value ?? 'buyer';
        $segment = $role === UserRole::MANUFACTURER->value
            ? 'dashboard/manufacturer/support-tickets'
            : 'dashboard/buyer/support-tickets';

        return MailNotificationHelper::frontendUrl($segment.'/'.$ticket->id);
    }

    private function adminTicketUrl(Ticket $ticket): string
    {
        return MailNotificationHelper::frontendUrl('admin/customer-supports/tickets/'.$ticket->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatchInApp(
        User $recipient,
        ?User $sender,
        string $type,
        string $title,
        string $body,
        array $data,
        string $actionUrl,
        ?int $senderId = null,
    ): void {
        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $recipient->id,
            type: $type,
            title: $title,
            body: $body,
            data: $data,
            actionUrl: $actionUrl,
            senderId: $senderId ?? $sender?->id,
        );
    }
}
