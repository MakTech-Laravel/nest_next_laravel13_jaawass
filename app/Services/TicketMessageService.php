<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class TicketMessageService
{
    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function sendMessage(
        Ticket $ticket,
        User $sender,
        ?string $message,
        array $attachments = [],
        ?string $sourceLocale = null,
    ): TicketMessage {
        return DB::transaction(function () use ($ticket, $sender, $message, $attachments, $sourceLocale): TicketMessage {
            $normalizedMessage = $this->normalizeMessage($message);

            $ticketMessage = TicketMessage::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $sender->id,
                'message' => $normalizedMessage,
            ]);

            $this->storeAttachments($ticket, $ticketMessage, $attachments);

            if ($normalizedMessage !== '') {
                $locale = $sourceLocale ?? app()->getLocale();
                $sourceData = ['message' => $normalizedMessage];

                $ticketMessage->upsertTranslations([
                    $locale => $sourceData,
                ]);

                $ticketMessage->autoTranslate(
                    sourceData: $sourceData,
                    sourceLocale: $locale,
                );
            }

            $ticketMessage->load(['user', 'attachments', 'translations']);

            return $ticketMessage;
        });
    }

    private function normalizeMessage(?string $message): string
    {
        return trim((string) $message);
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    private function storeAttachments(Ticket $ticket, TicketMessage $ticketMessage, array $attachments): void
    {
        if ($attachments === []) {
            return;
        }

        $disk = (string) Config::get('tickets.attachments.disk', 'public');

        foreach ($attachments as $attachment) {
            $path = $attachment->store('ticket-attachments/'.$ticket->id.'/'.$ticketMessage->id, [
                'disk' => $disk,
            ]);

            TicketAttachment::query()->create([
                'ticket_message_id' => $ticketMessage->id,
                'disk' => $disk,
                'file_path' => $path,
                'file_mime' => (string) $attachment->getClientMimeType(),
                'original_name' => $attachment->getClientOriginalName(),
                'size_bytes' => $attachment->getSize() ?? Storage::disk($disk)->size($path),
            ]);
        }
    }
}
