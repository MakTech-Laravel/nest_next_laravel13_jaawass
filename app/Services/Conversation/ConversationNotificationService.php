<?php

namespace App\Services\Conversation;

use App\Enums\MailTemplate;
use App\Enums\UserRole;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ConversationNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notifyMessageReceived(Message $message, Conversation $conversation, User $sender, User $recipient): void
    {
        if ((int) $recipient->id === (int) $sender->id) {
            return;
        }

        $senderName = MailNotificationHelper::displayName($sender);
        $preview = $this->messagePreview($message->body);
        $url = $this->conversationUrl($recipient, $conversation);

        MailNotificationHelper::sendIfEmail($recipient, function (string $email) use ($recipient, $senderName, $preview, $url): void {
            $this->mailingService->send($email, MailTemplate::ConversationMessageReceived, [
                'preheader' => __('mail.conversation_message_received.preheader', ['senderName' => $senderName]),
                'headerEyebrow' => __('mail.layout.default_eyebrow'),
                'headerTitle' => __('mail.conversation_message_received.header_title'),
                'headerSubtitle' => __('mail.conversation_message_received.header_subtitle'),
                'intro' => __('mail.conversation_message_received.intro', [
                    'name' => MailNotificationHelper::displayName($recipient),
                    'sender' => $senderName,
                    'senderName' => $senderName,
                ]),
                'messageHeading' => __('mail.conversation_message_received.message_heading'),
                'messageBody' => $preview ? nl2br(e($preview)) : null,
                'ctaUrl' => $url,
                'ctaLabel' => __('mail.conversation_message_received.cta'),
                'footerNote' => __('mail.conversation_message_received.footer'),
            ]);
        }, 'conversation.message');

        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $recipient->id,
            type: 'conversation.message',
            title: __('mail.conversation_message_received.notification_title'),
            body: __('mail.conversation_message_received.notification_body', ['sender' => $senderName]),
            data: [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
            ],
            actionUrl: $url,
            senderId: $sender->id,
        );
    }

    private function messagePreview(?string $body): ?string
    {
        $trimmed = trim((string) $body);

        if ($trimmed === '') {
            return null;
        }

        return mb_strlen($trimmed) > 280 ? mb_substr($trimmed, 0, 277).'...' : $trimmed;
    }

    private function conversationUrl(User $recipient, Conversation $conversation): string
    {
        $segment = match ($recipient->role) {
            UserRole::MANUFACTURER => 'dashboard/manufacturer/messages',
            UserRole::ADMIN => 'admin/messages',
            default => 'dashboard/buyer/messages',
        };

        return MailNotificationHelper::frontendUrl($segment.'?conversation='.$conversation->id);
    }
}
