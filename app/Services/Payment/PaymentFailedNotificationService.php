<?php

namespace App\Services\Payment;

use App\Enums\MailTemplate;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class PaymentFailedNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notify(User $manufacturer, ?string $planName = null): void
    {
        $manufacturer->loadMissing('subscription.plan');

        $resolvedPlanName = $planName
            ?? $manufacturer->subscription?->plan?->name
            ?? __('subscription.plan');

        MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($manufacturer, $resolvedPlanName): void {
            $this->mailingService->send($email, MailTemplate::PaymentFailed, [
                'name' => MailNotificationHelper::displayName($manufacturer),
                'manufacturerName' => MailNotificationHelper::displayName($manufacturer),
                'planName' => $resolvedPlanName,
                'failedAt' => now()->format('F j, Y'),
                'ctaUrl' => MailNotificationHelper::frontendUrl('dashboard/manufacturer/subscription'),
            ]);
        });
    }
}
