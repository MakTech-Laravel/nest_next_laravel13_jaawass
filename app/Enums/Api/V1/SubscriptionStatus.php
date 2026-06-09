<?php

namespace App\Enums\Api\V1;

enum SubscriptionStatus : string
{
    
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case PAST_DUE = 'past_due';
    case TRIALING = 'trialing';
    
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CANCELED => 'Canceled',
            self::PAST_DUE => 'Past Due',
            self::TRIALING => 'Trialing',
        };
    }
}
