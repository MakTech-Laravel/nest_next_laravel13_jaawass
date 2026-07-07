<?php

namespace App\Services\Auth;

use App\Enums\MailTemplate;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;
use Illuminate\Http\Request;

class PasswordChangedNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notify(User $user, ?Request $request = null): void
    {
        MailNotificationHelper::sendIfEmail($user, function (string $email) use ($user, $request): void {
            $this->mailingService->send($email, MailTemplate::PasswordChanged, [
                'name' => MailNotificationHelper::displayName($user),
                'accountEmail' => $user->email,
                'changedAt' => now()->format('F j, Y g:i A T'),
                'device' => $request?->userAgent() ?? __('mail.password_changed.device_unknown'),
                'location' => $request?->ip() ?? null,
                'ctaUrl' => MailNotificationHelper::frontendUrl('dashboard'),
            ]);
        });
    }
}
