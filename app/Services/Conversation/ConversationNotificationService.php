<?php

namespace App\Services\Conversation;

use App\Enums\MailTemplate;
use App\Enums\UserRole;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RfqSubmission;
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

        $sender->loadMissing('company');
        $recipient->loadMissing('company');

        $senderName = MailNotificationHelper::displayName($sender);
        $senderCompany = $sender->company?->company_name;
        $senderDisplayName = $senderCompany ? $senderName.' — '.$senderCompany : $senderName;
        $preview = $this->messagePreview($message->body);
        $url = $this->conversationUrl($recipient, $conversation);
        $inboxUrl = MailNotificationHelper::frontendUrl(
            $recipient->role === UserRole::MANUFACTURER
                ? 'dashboard/manufacturer/messages'
                : 'dashboard/buyer/messages'
        );
        $rfq = RfqSubmission::query()
            ->where('conversation_id', $conversation->id)
            ->with('product')
            ->first();

        MailNotificationHelper::sendIfEmail($recipient, function (string $email) use (
            $recipient,
            $sender,
            $senderName,
            $senderDisplayName,
            $preview,
            $url,
            $inboxUrl,
            $message,
            $rfq,
        ): void {
            $this->mailingService->send($email, MailTemplate::ConversationMessageReceived, [
                'recipientName' => MailNotificationHelper::displayName($recipient),
                'recipientRole' => $recipient->role?->value,
                'senderName' => $senderName,
                'senderRole' => $sender->role?->value,
                'senderDisplayName' => $senderDisplayName,
                'senderMeta' => $this->senderMeta($sender),
                'senderInitials' => MailNotificationHelper::initials($senderName),
                'messagePreview' => $preview ? nl2br(e($preview)) : null,
                'messageTimestamp' => $message->created_at?->format('M j, g:i A') ?? now()->format('M j, g:i A'),
                'inquiryTags' => $this->inquiryTags($rfq),
                'ctaUrl' => $url,
                'inboxUrl' => $inboxUrl,
                'ctaLabel' => __('mail.conversation_message_received.'.($recipient->role === UserRole::MANUFACTURER ? 'manufacturer' : 'buyer').'.cta'),
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

    private function senderMeta(User $sender): ?string
    {
        $country = $sender->company?->country;

        return match ($sender->role) {
            UserRole::MANUFACTURER => __('mail.rfq_quoted_buyer.supplier_meta').($country ? ' · '.$country : ''),
            UserRole::BUYER => __('mail.demo.badges.buyer').($country ? ' · '.$country : ''),
            default => null,
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function inquiryTags(?RfqSubmission $rfq): array
    {
        if ($rfq === null) {
            return [];
        }

        return array_values(array_filter([
            $rfq->rfq_number ? ['label' => 'RFQ', 'value' => $rfq->rfq_number] : null,
            $rfq->product?->name ? ['label' => 'Product', 'value' => $rfq->product->name] : null,
            $rfq->quantity !== null ? ['label' => 'Qty', 'value' => $rfq->quantity.' '.($rfq->quantity_unit ?? '')] : null,
        ]));
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
