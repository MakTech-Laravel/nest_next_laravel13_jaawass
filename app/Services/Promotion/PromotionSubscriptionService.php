<?php

namespace App\Services\Promotion;

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Subscription\PlanEntitlementResolver;
use App\Services\Subscription\SubscriptionLogService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\DB;

class PromotionSubscriptionService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionLogService $subscriptionLogService,
        private readonly PlanEntitlementResolver $entitlementResolver,
        private readonly PromotionService $promotionService,
    ) {}

    public function syncOnParticipantAccepted(Promotion $promotion, User $manufacturer): void
    {
        $manufacturer->loadMissing('subscription');

        if ($manufacturer->subscription !== null) {
            return;
        }

        $trialEndsAt = $this->promotionService->trialEndsAt($promotion);

        DB::transaction(function () use ($promotion, $manufacturer, $trialEndsAt): void {
            $this->subscriptionService->createSubscription([
                'manufacturer_id' => $manufacturer->id,
                'plan_id' => $promotion->plan_id,
                'billing_interval' => $this->resolvePostTrialBillingInterval($promotion)->value,
                'status' => SubscriptionStatus::TRIALING->value,
                'starts_at' => now(),
                'ends_at' => $trialEndsAt,
                'trial_ends_at' => $trialEndsAt,
                'auto_renew' => false,
            ]);

            $this->subscriptionLogService->createSubscriptionLog([
                'manufacturer_id' => $manufacturer->id,
                'to_plan_id' => $promotion->plan_id,
                'paid_amount' => (float) ($promotion->promotional_price ?? 0),
                'event_type' => SubscriptionEventType::SUBSCRIPTION_CREATED->value,
            ]);
        });

        $this->entitlementResolver->forget($manufacturer);
    }

    private function resolvePostTrialBillingInterval(Promotion $promotion): BillingInterval
    {
        $unit = strtolower((string) ($promotion->billing_period_unit ?? BillingInterval::MONTH->value));

        return $unit === BillingInterval::YEAR->value
            ? BillingInterval::YEAR
            : BillingInterval::MONTH;
    }
}
