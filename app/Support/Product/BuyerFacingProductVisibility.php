<?php

namespace App\Support\Product;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Product visibility for buyer-facing APIs (public + buyer routes).
 * Not applied to manufacturer or admin product endpoints.
 */
class BuyerFacingProductVisibility
{
    public function applyManufacturerSubscriptionConstraint(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->whereNotNull($query->qualifyColumn('user_id'))
            ->whereHas('user', function (Builder $user): void {
                $user->isManufacturer()
                    ->whereHas('subscription', fn (Builder $subscription) => $subscription->entitlementActive());
            });
    }

    public function productHasManufacturerWithActiveSubscription(Product $product): bool
    {
        if ($product->user_id === null) {
            return false;
        }

        $manufacturer = $product->relationLoaded('user')
            ? $product->user
            : $product->user()->with('subscription')->first();

        return $this->manufacturerQualifies($manufacturer);
    }

    public function qualifiesForPublicCatalog(Product $product): bool
    {
        if ($product->status !== 'active' || ! $product->is_approved) {
            return false;
        }

        return $this->productHasManufacturerWithActiveSubscription($product);
    }

    public function manufacturerQualifies(?User $manufacturer): bool
    {
        if ($manufacturer === null) {
            return false;
        }

        if (! $this->manufacturerHasManufacturerRole($manufacturer)) {
            return false;
        }

        $subscription = $manufacturer->relationLoaded('subscription')
            ? $manufacturer->subscription
            : $manufacturer->subscription()->first();

        return $subscription !== null && $subscription->isEntitlementActive();
    }

    private function manufacturerHasManufacturerRole(User $manufacturer): bool
    {
        $role = $manufacturer->role;

        if ($role instanceof UserRole) {
            return $role->isManufacturer();
        }

        return (string) $role === UserRole::MANUFACTURER->value;
    }
}
