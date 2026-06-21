<?php

namespace App\Jobs\Subscription;

use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSubscriptionInAppNotificationJob implements ShouldQueue
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
    ) {
        $this->onQueue((string) config('subscription.queue', 'default'));
    }

    public function handle(UserNotificationService $userNotificationService): void
    {
        $recipient = User::query()->find($this->recipientId);

        if ($recipient === null) {
            return;
        }

        $userNotificationService->notify(
            $recipient,
            $this->type,
            $this->title,
            $this->body,
            $this->data,
            $this->actionUrl,
        );
    }
}
