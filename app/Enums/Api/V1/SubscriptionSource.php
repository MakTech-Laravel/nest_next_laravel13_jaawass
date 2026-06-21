<?php

namespace App\Enums\Api\V1;

enum SubscriptionSource: string
{
    case PURCHASE = 'purchase';
    case PROMOTION = 'promotion';
}
