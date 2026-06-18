<?php

namespace App\Services\Subscription;

use App\Models\User;

class PlanEntitlementResolver
{
    /** @var array<int, PlanEntitlementService> */
    private array $cache = [];

    public function for(User $user): PlanEntitlementService
    {
        return $this->cache[$user->id] ??= new PlanEntitlementService($user);
    }

    public function forget(User $user): void
    {
        unset($this->cache[$user->id]);
    }
}
