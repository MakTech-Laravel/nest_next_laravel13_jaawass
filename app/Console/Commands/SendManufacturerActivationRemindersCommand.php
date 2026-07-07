<?php

namespace App\Console\Commands;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerActivationReminderService;
use Illuminate\Console\Command;

class SendManufacturerActivationRemindersCommand extends Command
{
    protected $signature = 'manufacturer:send-activation-reminders';

    protected $description = 'Queue activation reminder emails for approved manufacturers without active subscriptions.';

    public function handle(ManufacturerActivationReminderService $service): int
    {
        $reminderDays = (int) config('mailing.activation_reminder_days', 3);
        $targetDate = now()->subDays($reminderDays)->toDateString();
        $dispatched = 0;

        User::query()
            ->where('role', UserRole::MANUFACTURER->value)
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->whereNull('manufacturer_activation_reminder_sent_at')
            ->whereDate('manufacture_status_at', '<=', $targetDate)
            ->whereDoesntHave('subscription', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '>', now()))
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($service, &$dispatched): void {
                foreach ($users as $user) {
                    $service->sendReminder($user);
                    $dispatched++;
                }
            });

        $this->info("Sent {$dispatched} manufacturer activation reminder email(s).");

        return self::SUCCESS;
    }
}
