<?php

namespace App\Enums\Api\V1\Payment;


enum RegisterPaymentManager: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';

}
