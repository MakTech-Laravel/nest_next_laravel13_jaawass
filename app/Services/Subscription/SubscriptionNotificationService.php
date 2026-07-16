<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\MailTemplate;
use App\Jobs\Subscription\SendSubscriptionInAppNotificationJob;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class SubscriptionNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function sendExpiryReminder(Subscription $subscription): void
    {
        $subscription->loadMissing(['manufacturer', 'plan']);
        $manufacturer = $subscription->manufacturer;

        if ($manufacturer === null || $manufacturer->email === null) {
            return;
        }

        $daysRemaining = $this->daysRemaining($subscription);
        $plansUrl = $this->plansUrl();

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::SubscriptionExpiryReminder,
            [
                'name' => $this->displayName($manufacturer),
                'planName' => $subscription->plan?->name ?? __('subscription.plan'),
                'endsAt' => $subscription->ends_at?->format('F j, Y') ?? '',
                'daysRemaining' => $daysRemaining,
                'plansUrl' => $plansUrl,
            ],
        );

        $this->dispatchInAppNotification(
            $manufacturer,
            'plan.subscription.expiry_reminder',
            __('mail.subscription_expiry_reminder.notification_title'),
            __('mail.subscription_expiry_reminder.notification_body', [
                'plan' => $subscription->plan?->name ?? __('subscription.plan'),
                'days' => $daysRemaining,
            ]),
            [
                'category' => 'subscription',
                'subscription_id' => $subscription->id,
                'days_until' => $daysRemaining,
            ],
            $plansUrl,
        );
    }

    public function sendExpiredNotice(Subscription $subscription): void
    {
        $subscription->loadMissing(['manufacturer', 'plan']);
        $manufacturer = $subscription->manufacturer;

        if ($manufacturer === null || $manufacturer->email === null) {
            return;
        }

        $plansUrl = $this->plansUrl();

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::SubscriptionExpired,
            [
                'name' => $this->displayName($manufacturer),
                'planName' => $subscription->plan?->name ?? __('subscription.plan'),
                'endsAt' => $subscription->ends_at?->format('F j, Y') ?? '',
                'plansUrl' => $plansUrl,
            ],
        );

        $this->dispatchInAppNotification(
            $manufacturer,
            'plan.subscription.expired',
            __('mail.subscription_expired.notification_title'),
            __('mail.subscription_expired.notification_body', [
                'plan' => $subscription->plan?->name ?? __('subscription.plan'),
            ]),
            [
                'category' => 'subscription',
                'subscription_id' => $subscription->id,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_EXPIRED->value,
            ],
            $plansUrl,
        );
    }

    public function sendSubscriptionCreated(Subscription $subscription, ?float $paidAmount = null): void
    {
        $subscription->loadMissing(['manufacturer', 'plan']);
        $manufacturer = $subscription->manufacturer;

        if ($manufacturer === null || $manufacturer->email === null) {
            return;
        }

        $this->sendActivatedMail($manufacturer, $subscription, $paidAmount);

        $manufacturer->forceFill(['manufacturer_first_payment_reminder_sent_at' => now()])->save();

        $this->dispatchInAppNotification(
            $manufacturer,
            'plan.subscription.created',
            __('mail.subscription_created.notification_title'),
            __('mail.subscription_created.notification_body', [
                'plan' => $subscription->plan?->name ?? __('subscription.plan'),
            ]),
            [
                'category' => 'subscription',
                'subscription_id' => $subscription->id,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_CREATED->value,
            ],
            $this->plansUrl(),
        );
    }

    public function sendSubscriptionRenewed(Subscription $subscription, ?float $paidAmount = null): void
    {
        $subscription->loadMissing(['manufacturer', 'plan']);
        $manufacturer = $subscription->manufacturer;

        if ($manufacturer === null || $manufacturer->email === null) {
            return;
        }

        // For now always use the subscription-activated template on any payment.
        $this->sendActivatedMail($manufacturer, $subscription, $paidAmount);

        $this->dispatchInAppNotification(
            $manufacturer,
            'plan.subscription.renewed',
            __('mail.subscription_renewed.notification_title'),
            __('mail.subscription_renewed.notification_body', [
                'plan' => $subscription->plan?->name ?? __('subscription.plan'),
            ]),
            [
                'category' => 'subscription',
                'subscription_id' => $subscription->id,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_RENEWED->value,
            ],
            $this->plansUrl(),
        );
    }

    private function sendActivatedMail(User $manufacturer, Subscription $subscription, ?float $paidAmount): void
    {
        $enrollment = $this->enrollmentMailData($subscription, $paidAmount);
        $paidRaw = $enrollment['paidAmount'] ?? null;
        $paidAmountDisplay = $paidRaw !== null && $paidRaw !== ''
            ? '$'.ltrim((string) $paidRaw, '$').' USD'
            : null;

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::SubscriptionCreated,
            array_merge($enrollment, [
                'manufacturerName' => $this->displayName($manufacturer),
                'intro' => __('mail.subscription_created.intro', [
                    'name' => $this->displayName($manufacturer),
                    'plan' => $subscription->plan?->name ?? __('subscription.plan'),
                ]),
                'activatedAt' => now()->format('F j, Y'),
                'paidAmountDisplay' => $paidAmountDisplay,
                'ctaUrl' => MailNotificationHelper::frontendUrl('dashboard/manufacturer'),
                'productsUrl' => MailNotificationHelper::frontendUrl('dashboard/manufacturer/products'),
                'billingUrl' => MailNotificationHelper::frontendUrl('settings/billing'),
                'ctaLabel' => __('mail.subscription_created.cta'),
            ]),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatchInAppNotification(
        User $manufacturer,
        string $type,
        string $title,
        string $body,
        array $data,
        string $actionUrl,
    ): void {
        SendSubscriptionInAppNotificationJob::dispatch(
            $manufacturer->id,
            $type,
            $title,
            $body,
            $data,
            $actionUrl,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentMailData(Subscription $subscription, ?float $paidAmount): array
    {
        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status->value
            : (string) $subscription->status;

        return [
            'manufacturerName' => $this->displayName($subscription->manufacturer),
            'planName' => $subscription->plan?->name ?? __('subscription.plan'),
            'billingInterval' => $subscription->billing_interval,
            'startsAt' => $subscription->starts_at?->format('F j, Y') ?? '',
            'endsAt' => $subscription->ends_at?->format('F j, Y') ?? '',
            'paidAmount' => $paidAmount !== null ? number_format($paidAmount, 2) : null,
            'status' => $status,
            'plansUrl' => $this->plansUrl(),
        ];
    }

    private function daysRemaining(Subscription $subscription): int
    {
        if ($subscription->ends_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($subscription->ends_at, false));
    }

    private function displayName(User $user): string
    {
        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : 'there';
    }

    private function plansUrl(): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = '/'.ltrim((string) config('subscription.plans_path', '/plans'), '/');

        return $frontendUrl.$path;
    }
}
