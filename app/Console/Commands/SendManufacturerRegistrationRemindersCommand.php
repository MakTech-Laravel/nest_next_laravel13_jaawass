<?php

namespace App\Console\Commands;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Registration\ManufacturerRegistrationReminderService;
use Illuminate\Console\Command;

class SendManufacturerRegistrationRemindersCommand extends Command
{
    protected $signature = 'registration:send-manufacturer-reminders';

    protected $description = 'Queue manufacturer registration reminder emails for pending accounts.';

    public function handle(ManufacturerRegistrationReminderService $service): int
    {
        $reminderDays = (int) config('mailing.registration_reminder_days', 3);
        $targetDate = now()->subDays($reminderDays)->toDateString();
        $dispatched = 0;

        User::query()
            ->where('role', UserRole::MANUFACTURER->value)
            ->where('manufacture_status', UserManuFactureStatus::PENDING->value)
            ->whereNull('manufacturer_registration_reminder_sent_at')
            ->whereDate('created_at', '<=', $targetDate)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($service, &$dispatched): void {
                foreach ($users as $user) {
                    $service->sendReminder($user);
                    $dispatched++;
                }
            });

        $this->info("Sent {$dispatched} manufacturer registration reminder email(s).");

        return self::SUCCESS;
    }
}
