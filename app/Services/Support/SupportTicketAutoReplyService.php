<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketMessageService;
use Illuminate\Support\Facades\Log;

final class SupportTicketAutoReplyService
{
    public function __construct(
        private readonly TicketMessageService $ticketMessageService,
        private readonly SupportTicketNotificationService $supportTicketNotificationService,
    ) {}

    /**
     * Post a canned acknowledgment when a customer replies on a ticket,
     * and email them via the dedicated support-ticket-auto-reply template.
     */
    public function replyOnUserMessage(Ticket $ticket, ?string $locale = null): ?TicketMessage
    {
        if (! (bool) config('tickets.auto_reply.enabled', true)) {
            return null;
        }

        $sender = $this->resolveSender($ticket);

        if ($sender === null) {
            Log::warning('Support ticket auto-reply skipped: no sender available.', [
                'ticket_id' => $ticket->id,
            ]);

            return null;
        }

        $message = trim((string) __('tickets.auto_reply.message', [
            'subject' => (string) $ticket->subject,
            'ticketNumber' => 'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
        ], $locale ?? app()->getLocale()));

        if ($message === '') {
            return null;
        }

        $ticketMessage = $this->ticketMessageService->sendMessage(
            $ticket,
            $sender,
            $message,
            [],
            $locale,
            true,
        );

        $this->supportTicketNotificationService->notifyReply(
            $ticket->fresh(['user', 'assignee']),
            $sender,
            $message,
        );

        return $ticketMessage;
    }

    private function resolveSender(Ticket $ticket): ?User
    {
        $configuredUserId = config('tickets.auto_reply.user_id');

        if (is_int($configuredUserId) && $configuredUserId > 0) {
            $configured = User::query()->find($configuredUserId);

            if ($configured !== null) {
                return $configured;
            }
        }

        $ticket->loadMissing('assignee');

        if ($ticket->assignee !== null) {
            return $ticket->assignee;
        }

        return User::query()->isAdmin()->orderBy('id')->first();
    }
}
