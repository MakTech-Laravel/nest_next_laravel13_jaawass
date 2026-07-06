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

        MailNotificationHelper::sendIfEmail($creator, function (string $email) use ($creator, $subject, $preview, $userUrl, $ticket): void {
            $this->mailingService->send($email, MailTemplate::SupportTicketCreated, $this->mailData(
                'mail.support_ticket_created',
                ['name' => MailNotificationHelper::displayName($creator), 'subject' => $subject],
                $preview,
                $userUrl,
                __('mail.support_ticket_created.cta'),
                'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
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
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($creator, $subject, $preview, $adminUrl, $ticket): void {
                $this->mailingService->send($email, MailTemplate::SupportTicketCreatedAdmin, $this->mailData(
                    'mail.support_ticket_created_admin',
                    [
                        'user' => MailNotificationHelper::displayName($creator),
                        'subject' => $subject,
                    ],
                    $preview,
                    $adminUrl,
                    __('mail.support_ticket_created_admin.cta'),
                    'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
                ));
            });

            $this->dispatchInApp(
                $admin,
                $creator,
                'support.ticket.created.admin',
                __('mail.support_ticket_created_admin.notification_title'),
                __('mail.support_ticket_created_admin.notification_body', [
                    'user' => MailNotificationHelper::displayName($creator),
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
        $recipient = $this->replyRecipient($ticket, $sender);

        if ($recipient === null) {
            return;
        }

        $url = $recipient->role === UserRole::ADMIN
            ? $this->adminTicketUrl($ticket)
            : $this->userTicketUrl($ticket, $recipient);

        MailNotificationHelper::sendIfEmail($recipient, function (string $email) use ($recipient, $sender, $subject, $preview, $url, $ticket): void {
            $this->mailingService->send($email, MailTemplate::SupportTicketReply, $this->mailData(
                'mail.support_ticket_reply',
                [
                    'name' => MailNotificationHelper::displayName($recipient),
                    'sender' => MailNotificationHelper::displayName($sender),
                    'senderName' => MailNotificationHelper::displayName($sender),
                    'subject' => $subject,
                ],
                $preview,
                $url,
                __('mail.support_ticket_reply.cta'),
                'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
            ));
        });

        $this->dispatchInApp(
            $recipient,
            $sender,
            'support.ticket.reply',
            __('mail.support_ticket_reply.notification_title'),
            __('mail.support_ticket_reply.notification_body', [
                'sender' => MailNotificationHelper::displayName($sender),
                'subject' => $subject,
            ]),
            ['ticket_id' => $ticket->id],
            $url,
            $sender->id,
        );
    }

    public function notifyStatusChanged(Ticket $ticket, TicketStatus $status, ?User $actor = null): void
    {
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

        MailNotificationHelper::sendIfEmail($owner, function (string $email) use ($owner, $subject, $statusLabel, $url, $ticket): void {
            $this->mailingService->send($email, MailTemplate::SupportTicketResolved, $this->mailData(
                'mail.support_ticket_resolved',
                [
                    'name' => MailNotificationHelper::displayName($owner),
                    'subject' => $subject,
                    'status' => $statusLabel,
                ],
                null,
                $url,
                __('mail.support_ticket_resolved.cta'),
                'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
            ));
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

    private function replyRecipient(Ticket $ticket, User $sender): ?User
    {
        if ($ticket->assignee !== null && (int) $ticket->assignee->id !== (int) $sender->id) {
            return $ticket->assignee;
        }

        if ($ticket->user !== null && (int) $ticket->user->id !== (int) $sender->id) {
            return $ticket->user;
        }

        if ($sender->role === UserRole::ADMIN) {
            return $ticket->user;
        }

        return $ticket->assignee;
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
     * @return array<string, mixed>
     */
    private function mailData(
        string $prefix,
        array $replacements,
        ?string $messageBody,
        string $ctaUrl,
        string $ctaLabel,
        string $referenceId,
    ): array {
        return [
            'preheader' => __($prefix.'.preheader', $replacements),
            'headerEyebrow' => __('mail.layout.default_eyebrow'),
            'headerTitle' => __($prefix.'.header_title', $replacements),
            'headerSubtitle' => __($prefix.'.header_subtitle', $replacements),
            'intro' => __($prefix.'.intro', $replacements),
            'messageHeading' => $messageBody ? __($prefix.'.message_heading', $replacements) : null,
            'messageBody' => $messageBody ? nl2br(e($messageBody)) : null,
            'ctaUrl' => $ctaUrl,
            'ctaLabel' => $ctaLabel,
            'referenceId' => $referenceId,
            'footerNote' => __($prefix.'.footer', $replacements),
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
