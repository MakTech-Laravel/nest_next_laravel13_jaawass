<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionSource;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymentCheckService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionPurchaseService
{
    public function __construct(
        private readonly PaymentCheckService $paymentCheckService,
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionLogService $subscriptionLogService,
        private readonly SubscriptionNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{subscription: Subscription, created: bool}
     */
    public function confirmPurchase(User $manufacturer, array $payload): array
    {
        $existingPayment = Payment::query()
            ->where('payment_id', $payload['payment_id'])
            ->first();

        if ($existingPayment !== null) {
            return $this->handleIdempotentPayment($manufacturer, $existingPayment);
        }

        $verified = $this->paymentCheckService->verify(
            (string) $payload['payment_method'],
            $payload,
        );

        $plan = Plan::query()->findOrFail($payload['plan_id']);
        $subscriptionData = $this->buildSubscriptionData($manufacturer->id, $payload);

        $subscription = DB::transaction(function () use ($manufacturer, $verified, $plan, $subscriptionData): Subscription {
            $subscription = $this->subscriptionService->createSubscription($subscriptionData);

            $payment = Payment::query()->create([
                'payment_id' => $verified->externalId,
                'payment_method' => $verified->paymentMethod,
                'amount' => $verified->amount,
                'status' => 'paid',
                'source_id' => $plan->id,
                'source_type' => Plan::class,
                'user_id' => $manufacturer->id,
                'subscription_id' => $subscription->id,
            ]);

            $this->subscriptionLogService->createSubscriptionLog([
                'manufacturer_id' => $manufacturer->id,
                'to_plan_id' => $plan->id,
                'paid_amount' => $payment->amount,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_CREATED->value,
            ]);

            return $subscription->load(['manufacturer', 'plan']);
        });

        $this->notificationService->sendSubscriptionCreated($subscription, (float) $verified->amount);

        return ['subscription' => $subscription, 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{subscription: Subscription, created: bool}
     */
    public function renewPurchase(User $manufacturer, array $payload): array
    {
        $subscription = $manufacturer->subscription;

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription' => [__('subscription.no_subscripiton_found')],
            ]);
        }

        if ($subscription->isEntitlementActive()) {
            throw ValidationException::withMessages([
                'subscription' => [__('subscription.already_subscribed')],
            ]);
        }

        $existingPayment = Payment::query()
            ->where('payment_id', $payload['payment_id'])
            ->first();

        if ($existingPayment !== null) {
            return $this->handleIdempotentPayment($manufacturer, $existingPayment);
        }

        $verified = $this->paymentCheckService->verify(
            (string) $payload['payment_method'],
            $payload,
        );

        $plan = Plan::query()->findOrFail($payload['plan_id']);
        $fromPlanId = $subscription->plan_id;
        $subscriptionData = $this->buildSubscriptionData($manufacturer->id, $payload);

        $subscription = DB::transaction(function () use ($manufacturer, $subscription, $verified, $plan, $fromPlanId, $subscriptionData): Subscription {
            $updated = $this->subscriptionService->updateSubscription($subscription->id, $subscriptionData);

            $payment = Payment::query()->create([
                'payment_id' => $verified->externalId,
                'payment_method' => $verified->paymentMethod,
                'amount' => $verified->amount,
                'status' => 'paid',
                'source_id' => $plan->id,
                'source_type' => Plan::class,
                'user_id' => $manufacturer->id,
                'subscription_id' => $updated->id,
            ]);

            $this->subscriptionLogService->createSubscriptionLog([
                'manufacturer_id' => $manufacturer->id,
                'from_plan_id' => $fromPlanId,
                'to_plan_id' => $plan->id,
                'paid_amount' => $payment->amount,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_RENEWED->value,
            ]);

            return $updated->load(['manufacturer', 'plan']);
        });

        $this->notificationService->sendSubscriptionRenewed($subscription, (float) $verified->amount);

        return ['subscription' => $subscription, 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upgradePurchase(User $manufacturer, array $payload): Subscription
    {
        $subscription = $manufacturer->subscription;

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription' => [__('subscription.no_subscripiton_found')],
            ]);
        }

        if ((int) $subscription->plan_id === (int) $payload['plan_id']) {
            if ($subscription->isEntitlementActive()) {
                throw ValidationException::withMessages([
                    'plan_id' => [__('subscription.same_plan')],
                ]);
            }

            ['subscription' => $renewed] = $this->renewPurchase($manufacturer, $payload);

            return $renewed;
        }

        $existingPayment = Payment::query()
            ->where('payment_id', $payload['payment_id'])
            ->first();

        if ($existingPayment !== null) {
            ['subscription' => $replay] = $this->handleIdempotentPayment($manufacturer, $existingPayment);

            return $replay;
        }

        $verified = $this->paymentCheckService->verify(
            (string) $payload['payment_method'],
            $payload,
        );

        $oldPlan = Plan::query()->findOrFail($subscription->plan_id);
        $newPlan = Plan::query()->findOrFail($payload['plan_id']);
        $subscriptionData = $this->buildSubscriptionData($manufacturer->id, $payload);

        return DB::transaction(function () use ($manufacturer, $subscription, $verified, $oldPlan, $newPlan, $subscriptionData): Subscription {
            $updated = $this->subscriptionService->updateSubscription($subscription->id, $subscriptionData);

            Payment::query()->create([
                'payment_id' => $verified->externalId,
                'payment_method' => $verified->paymentMethod,
                'amount' => $verified->amount,
                'status' => 'paid',
                'source_id' => $newPlan->id,
                'source_type' => Plan::class,
                'user_id' => $manufacturer->id,
                'subscription_id' => $updated->id,
            ]);

            $eventType = $this->resolveUpgradeEventType($oldPlan, $newPlan);

            $this->subscriptionLogService->createSubscriptionLog([
                'manufacturer_id' => $manufacturer->id,
                'from_plan_id' => $oldPlan->id,
                'to_plan_id' => $newPlan->id,
                'paid_amount' => $verified->amount,
                'event_type' => $eventType->value,
            ]);

            return $updated->load(['manufacturer', 'plan']);
        });
    }

    public function cancelPurchase(User $manufacturer): Subscription
    {
        $subscription = $manufacturer->subscription;

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription' => [__('subscription.no_subscripiton_found')],
            ]);
        }

        $this->subscriptionService->cancelSubscriptionPlan($subscription->id);

        $this->subscriptionLogService->createSubscriptionLog([
            'manufacturer_id' => $manufacturer->id,
            'from_plan_id' => $subscription->plan_id,
            'to_plan_id' => $subscription->plan_id,
            'event_type' => SubscriptionEventType::SUBSCRIPTION_CANCELLED->value,
        ]);

        return $subscription->fresh(['manufacturer', 'plan']);
    }

    /**
     * @return array{subscription: Subscription, created: bool}
     */
    private function handleIdempotentPayment(User $manufacturer, Payment $payment): array
    {
        if ((int) $payment->user_id !== (int) $manufacturer->id) {
            throw new PaymentVerificationException(
                __('subscription.payment_already_used'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $subscription = Subscription::query()
            ->where('manufacturer_id', $manufacturer->id)
            ->with(['manufacturer', 'plan'])
            ->first();

        if ($subscription === null) {
            throw new PaymentVerificationException(
                __('subscription.payment_without_subscription'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return ['subscription' => $subscription, 'created' => false];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildSubscriptionData(int $manufacturerId, array $payload): array
    {
        $billingInterval = $this->normalizeBillingInterval((string) $payload['billing_interval']);
        $startsAt = Carbon::now();
        $endsAt = $billingInterval === BillingInterval::YEAR
            ? $startsAt->copy()->addYear()
            : $startsAt->copy()->addMonth();

        return [
            'manufacturer_id' => $manufacturerId,
            'plan_id' => (int) $payload['plan_id'],
            'billing_interval' => $billingInterval->value,
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'trial_ends_at' => null,
            'auto_renew' => (bool) $payload['auto_renew'],
            'expiry_reminder_sent_at' => null,
            'source' => SubscriptionSource::PURCHASE->value,
            'promotion_id' => null,
        ];
    }

    private function normalizeBillingInterval(string $interval): BillingInterval
    {
        $normalized = match (strtolower($interval)) {
            'month', 'monthly' => BillingInterval::MONTH,
            'year', 'yearly' => BillingInterval::YEAR,
            default => null,
        };

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'billing_interval' => [__('subscription.invalid_billing_interval')],
            ]);
        }

        return $normalized;
    }

    private function resolveUpgradeEventType(Plan $fromPlan, Plan $toPlan): SubscriptionEventType
    {
        $fromPrice = (float) $fromPlan->monthly_price;
        $toPrice = (float) $toPlan->monthly_price;

        if ($toPrice > $fromPrice) {
            return SubscriptionEventType::SUBSCRIPTION_UPGRADED;
        }

        if ($toPrice < $fromPrice) {
            return SubscriptionEventType::SUBSCRIPTION_DOWNGRADED;
        }

        return SubscriptionEventType::PLAN_CHANGED;
    }
}
