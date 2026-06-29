<?php

declare(strict_types=1);

namespace App\Jobs\Support;

use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSupportTicketInAppNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly int $recipientId,
        public readonly string $type,
        public readonly ?string $title,
        public readonly ?string $body,
        public readonly array $data = [],
        public readonly ?string $actionUrl = null,
        public readonly ?int $senderId = null,
    ) {}

    public function handle(UserNotificationService $userNotificationService): void
    {
        $recipient = User::query()->find($this->recipientId);

        if ($recipient === null) {
            return;
        }

        $sender = $this->senderId !== null
            ? User::query()->find($this->senderId)
            : null;

        $userNotificationService->notify(
            $recipient,
            $this->type,
            $this->title,
            $this->body,
            $this->data,
            $this->actionUrl,
            $sender,
        );
    }
}
