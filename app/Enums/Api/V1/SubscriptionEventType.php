<?php

namespace App\Enums\Api\V1;

enum SubscriptionEventType: string
{
    case PLAN_CHANGED = 'plan_changed';
    case SUBSCRIPTION_CANCELLED = 'subscription_cancelled';
    case SUBSCRIPTION_RENEWED = 'subscription_renewed';
    case SUBSCRIPTION_CREATED = 'subscription_created';
    case SUBSCRIPTION_EXPIRED = 'subscription_expired';
    case SUBSCRIPTION_SUSPENDED = 'subscription_suspended';
    case SUBSCRIPTION_DOWNGRADED = 'subscription_downgraded';
    case SUBSCRIPTION_UPGRADED = 'subscription_upgraded';
}
