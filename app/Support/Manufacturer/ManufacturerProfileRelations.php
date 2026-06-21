<?php

namespace App\Support\Manufacturer;

use App\Models\User;

class ManufacturerProfileRelations
{
    /**
     * Relations eager-loaded whenever a manufacturer fetches their own profile
     * from authenticated (non-public) API endpoints.
     *
     * @return array<int|string, mixed>
     */
    public static function relations(): array
    {
        return [
            'company.industries',
            'factoryImages',
            'preferredCurrency',
            'manufacturerReviews',
            'subscription.plan.planFeatures.feature',
            'subscriptionLogs.fromPlan',
            'subscriptionLogs.toPlan',
            'additionalInformationRequests.responses',
            'additionalInformationRequests.requestedBy',
        ];
    }

    public static function load(User $user): User
    {
        if (! $user->role?->isManufacturer()) {
            return $user;
        }

        return $user->loadMissing(static::relations());
    }
}
