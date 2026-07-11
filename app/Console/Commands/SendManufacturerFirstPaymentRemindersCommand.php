<?php

namespace App\Console\Commands;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerFirstPaymentReminderService;
use Illuminate\Console\Command;

class SendManufacturerFirstPaymentRemindersCommand extends Command
{
    protected $signature = 'manufacturer:send-first-payment-reminders';

    protected $description = 'Queue first-payment reminder emails for approved manufacturers without an active subscription.';

    public function handle(ManufacturerFirstPaymentReminderService $service): int
    {
        $reminderDays = (int) config('mailing.first_payment_reminder_days', 3);
        $targetDate = now()->subDays($reminderDays)->toDateString();
        $dispatched = 0;

        User::query()
            ->where('role', UserRole::MANUFACTURER->value)
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->whereNull('manufacturer_first_payment_reminder_sent_at')
            ->whereDate('manufacture_status_at', '<=', $targetDate)
            ->whereDoesntHave('subscription', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '>', now()))
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($service, &$dispatched): void {
                foreach ($users as $user) {
                    $service->sendReminder($user);
                    $dispatched++;
                }
            });

        $this->info("Sent {$dispatched} manufacturer first-payment reminder email(s).");

        return self::SUCCESS;
    }
}
