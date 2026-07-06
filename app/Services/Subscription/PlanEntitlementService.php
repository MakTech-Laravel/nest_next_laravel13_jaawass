<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\PlanFeatureKey;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Exceptions\Subscription\PlanEntitlementException;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PlanEntitlementService
{
    /** @var Collection<string, array{input_type: string, value: string}>|null */
    private ?Collection $featureMap = null;

    public function __construct(
        private readonly User $manufacturer,
    ) {}

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function subscription(): ?Subscription
    {
        return $this->activeSubscription();
    }

    public function hasFeature(string|PlanFeatureKey $featureKey): bool
    {
        $key = $featureKey instanceof PlanFeatureKey ? $featureKey->value : $featureKey;

      
        $feature = $this->features()->get($key);

       

        if ($feature === null) {
            return false;
        }

        if ($feature['input_type'] === 'boolean') {
            return $feature['value'] === '1';
        }

        return trim($feature['value']) !== '';
    }

    public function featureValue(string|PlanFeatureKey $featureKey): ?string
    {
        $key = $featureKey instanceof PlanFeatureKey ? $featureKey->value : $featureKey;
        $feature = $this->features()->get($key);

       
        return $feature['value'] ?? null;
    }

    /**
     * Returns null when unlimited, 0 when feature is unavailable.
     */
    public function numericLimit(string|PlanFeatureKey $featureKey): ?int
    {
        if (! $this->hasFeature($featureKey)) {
            return 0;
        }

        $value = strtolower(trim((string) $this->featureValue($featureKey)));

        if ($value === '' || in_array($value, ['unlimited', 'null', 'none'], true)) {
            return null;
        }

        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        return null;
    }

    public function hasAnyFeature(string ...$featureKeys): bool
    {
        foreach ($featureKeys as $featureKey) {
            if ($this->hasFeature($featureKey)) {
                return true;
            }
        }

        return false;
    }

    public function assertActiveSubscription(): void
    {
        if (! $this->hasActiveSubscription()) {
            throw PlanEntitlementException::noActiveSubscription();
        }
    }

    public function assertFeature(string|PlanFeatureKey $featureKey): void
    {
        $this->assertActiveSubscription();

        $key = $featureKey instanceof PlanFeatureKey ? $featureKey->value : $featureKey;

        if (! $this->hasFeature($key)) {
            throw PlanEntitlementException::featureNotAvailable($key);
        }
    }

    public function assertAnyFeature(string ...$featureKeys): void
    {
        $this->assertActiveSubscription();

        if (! $this->hasAnyFeature(...$featureKeys)) {
            $key = $featureKeys[0] ?? 'feature';

            throw PlanEntitlementException::featureNotAvailable($key);
        }
    }

    public function assertWithinLimit(string|PlanFeatureKey $featureKey, int $currentUsage, int $increment = 1): void
    {
        $key = $featureKey instanceof PlanFeatureKey ? $featureKey->value : $featureKey;

        $this->assertFeature($key);

        $limit = $this->numericLimit($key);

        if ($limit === null) {
            return;
        }

        if (($currentUsage + $increment) > $limit) {
            throw PlanEntitlementException::limitExceeded($key, $limit);
        }
    }

    /**
     * @return Collection<string, array{input_type: string, value: string}>
     */
    public function features(): Collection
    {
        if ($this->featureMap !== null) {
            return $this->featureMap;
        }

        $subscription = $this->activeSubscription();

        if ($subscription === null) {
            return $this->featureMap = collect();
        }

        $subscription->loadMissing('plan.planFeatures.feature');

        $this->featureMap = $subscription->plan?->planFeatures
            ?->mapWithKeys(function (PlanFeature $planFeature): array {
                $key = $planFeature->feature?->key;

                if ($key === null || $key === '') {
                    return [];
                }

                return [
                    $key => [
                        'input_type' => $planFeature->input_type,
                        'value' => (string) $planFeature->value,
                    ],
                ];
            }) ?? collect();

        return $this->featureMap;
    }

    public function visibilityScore(): int
    {
        return match (true) {
            $this->hasFeature(PlanFeatureKey::PREMIUM_SEARCH_PLACEMENT) => 40,
            $this->hasFeature(PlanFeatureKey::MAXIMUM_BUYER_VISIBILITY) => 30,
            $this->hasFeature(PlanFeatureKey::PRIORITY_SEARCH_VISIBILITY) => 20,
            $this->hasFeature(PlanFeatureKey::ENHANCED_BUYER_VISIBILITY) => 10,
            $this->hasFeature(PlanFeatureKey::LIMITED_BUYER_VISIBILITY) => 5,
            default => 0,
        };
    }

    public static function activeSubscriptionQuery(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::TRIALING->value,
            ])
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    private function activeSubscription(): ?Subscription
    {
        $this->manufacturer->loadMissing('subscription.plan.planFeatures.feature');

        $subscription = $this->manufacturer->subscription;

        if ($subscription === null) {
            return null;
        }

        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status->value
            : (string) $subscription->status;

        if (! in_array($status, [
            SubscriptionStatus::ACTIVE->value,
            SubscriptionStatus::TRIALING->value,
        ], true)) {
            return null;
        }

        if ($subscription->ends_at !== null && $subscription->ends_at->isPast()) {
            return null;
        }

        return $subscription;
    }
}
