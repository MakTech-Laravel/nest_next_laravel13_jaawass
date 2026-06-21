<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionLifecycleService
{
    public function __construct(
        private readonly SubscriptionLogService $subscriptionLogService,
        private readonly SubscriptionNotificationService $notificationService,
        private readonly PlanEntitlementResolver $entitlementResolver,
    ) {}

    public function sendExpiryReminder(Subscription $subscription): bool
    {
        if (! $this->isEligibleForExpiryReminder($subscription)) {
            return false;
        }

        return DB::transaction(function () use ($subscription): bool {
            $locked = Subscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $this->isEligibleForExpiryReminder($locked)) {
                return false;
            }

            $this->notificationService->sendExpiryReminder($locked);

            $locked->update(['expiry_reminder_sent_at' => now()]);

            return true;
        });
    }

    public function processExpiredSubscription(Subscription $subscription): bool
    {
        if (! $this->isEligibleForExpiryProcessing($subscription)) {
            return false;
        }

        return DB::transaction(function () use ($subscription): bool {
            $locked = Subscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $this->isEligibleForExpiryProcessing($locked)) {
                return false;
            }

            $fromPlanId = $locked->plan_id;

            $locked->update([
                'status' => SubscriptionStatus::PAST_DUE->value,
            ]);

            $this->subscriptionLogService->createSubscriptionLog([
                'manufacturer_id' => $locked->manufacturer_id,
                'from_plan_id' => $fromPlanId,
                'to_plan_id' => $fromPlanId,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_EXPIRED->value,
            ]);

            $locked->load(['manufacturer', 'plan']);

            if ($locked->manufacturer !== null) {
                $this->entitlementResolver->forget($locked->manufacturer);
            }

            $this->notificationService->sendExpiredNotice($locked->fresh(['manufacturer', 'plan']));

            return true;
        });
    }

    public function isEligibleForExpiryReminder(Subscription $subscription): bool
    {
        if (! $subscription->isEntitlementActive()) {
            return false;
        }

        if ($subscription->ends_at === null || $subscription->expiry_reminder_sent_at !== null) {
            return false;
        }

        $reminderDays = (int) config('subscription.expiry_reminder_days', 7);
        $targetDate = now()->addDays($reminderDays)->toDateString();

        return $subscription->ends_at->toDateString() === $targetDate;
    }

    public function isEligibleForExpiryProcessing(Subscription $subscription): bool
    {
        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status->value
            : (string) $subscription->status;

        if (! in_array($status, [
            SubscriptionStatus::ACTIVE->value,
            SubscriptionStatus::TRIALING->value,
        ], true)) {
            return false;
        }

        return $subscription->ends_at !== null && $subscription->ends_at->isPast();
    }
}
