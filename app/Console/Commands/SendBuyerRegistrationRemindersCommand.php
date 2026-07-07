<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Registration\BuyerRegistrationReminderService;
use Illuminate\Console\Command;

class SendBuyerRegistrationRemindersCommand extends Command
{
    protected $signature = 'registration:send-buyer-reminders';

    protected $description = 'Queue buyer registration reminder emails for incomplete accounts.';

    public function handle(BuyerRegistrationReminderService $service): int
    {
        $reminderDays = (int) config('mailing.registration_reminder_days', 3);
        $targetDate = now()->subDays($reminderDays)->toDateString();
        $dispatched = 0;

        User::query()
            ->where('role', UserRole::BUYER->value)
            ->whereNull('buyer_registration_reminder_sent_at')
            ->whereNull('email_verified_at')
            ->whereDate('created_at', '<=', $targetDate)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($service, &$dispatched): void {
                foreach ($users as $user) {
                    $service->sendReminder($user);
                    $dispatched++;
                }
            });

        $this->info("Sent {$dispatched} buyer registration reminder email(s).");

        return self::SUCCESS;
    }
}
