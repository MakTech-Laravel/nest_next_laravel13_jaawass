<?php

namespace App\Enums\Api\V1;

enum BillingInterval: string
{
    case MONTH = 'month';
    case YEAR = 'year';
    
    public function label(): string
    {
        return match ($this) {
            self::MONTH => 'Monthly',
            self::YEAR => 'Yearly',
        };
    }
    
}
