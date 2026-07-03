<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\MailTemplate;
use App\Jobs\Subscription\SendSubscriptionInAppNotificationJob;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Mailing\MailingService;
use Illuminate\Support\Facades\Lang;

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
            $this->transactionalMail('mail.subscription_expiry_reminder', [
                'manufacturerName' => $this->displayName($manufacturer),
                'planName' => $subscription->plan?->name ?? __('subscription.plan'),
                'endsAt' => $subscription->ends_at?->format('F j, Y') ?? '',
                'daysRemaining' => $daysRemaining,
                'plansUrl' => $plansUrl,
            ], [
                'plan' => $subscription->plan?->name ?? __('subscription.plan'),
                'days' => $daysRemaining,
                'date' => $subscription->ends_at?->format('F j, Y') ?? '',
            ]),
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
            $this->transactionalMail('mail.subscription_expired', [
                'manufacturerName' => $this->displayName($manufacturer),
                'planName' => $subscription->plan?->name ?? __('subscription.plan'),
                'endedAt' => $subscription->ends_at?->format('F j, Y') ?? '',
                'plansUrl' => $plansUrl,
            ], [
                'plan' => $subscription->plan?->name ?? __('subscription.plan'),
                'date' => $subscription->ends_at?->format('F j, Y') ?? '',
            ]),
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

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::SubscriptionCreated,
            $this->transactionalMail(
                'mail.subscription_created',
                $this->enrollmentMailData($subscription, $paidAmount),
                ['plan' => $subscription->plan?->name ?? __('subscription.plan')],
            ),
        );

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

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::SubscriptionRenewed,
            $this->transactionalMail(
                'mail.subscription_renewed',
                $this->enrollmentMailData($subscription, $paidAmount),
                ['plan' => $subscription->plan?->name ?? __('subscription.plan')],
            ),
        );

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
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $replacements
     * @return array<string, mixed>
     */
    private function transactionalMail(string $prefix, array $base, array $replacements = []): array
    {
        $name = $base['manufacturerName'] ?? 'there';
        $plan = $base['planName'] ?? __('subscription.plan');
        $merge = array_merge(['name' => $name, 'plan' => $plan], $replacements);

        return array_merge($base, [
            'preheader' => __($prefix.'.preheader', $merge),
            'headerEyebrow' => __('mail.layout.default_eyebrow'),
            'headerTitle' => __($prefix.'.subject', $merge),
            'headerSubtitle' => $plan,
            'intro' => __($prefix.'.intro', $merge),
            'extraBody' => Lang::has($prefix.'.body') ? __($prefix.'.body', $merge) : null,
            'detailsHeading' => Lang::has($prefix.'.details_heading') ? __($prefix.'.details_heading', $merge) : null,
            'details' => Lang::has($prefix.'.details_heading') ? array_filter([
                __('mail.subscription_created.billing_interval') => $base['billingInterval'] ?? null,
                __('mail.subscription_created.starts_at') => $base['startsAt'] ?? null,
                __('mail.subscription_created.ends_at') => $base['endsAt'] ?? null,
                __('mail.subscription_created.paid_amount') => $base['paidAmount'] ?? null,
            ]) : [],
            'ctaUrl' => $base['plansUrl'] ?? $this->plansUrl(),
            'ctaLabel' => __($prefix.'.cta'),
            'footerNote' => __($prefix.'.footer'),
        ]);
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
