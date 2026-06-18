<?php

namespace App\Enums\Api\V1\Payment;


enum RegisterPaymentManager: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
