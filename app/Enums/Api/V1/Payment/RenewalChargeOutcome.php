<?php

namespace App\Enums\Api\V1\Payment;

enum RenewalChargeOutcome: string
{
    case SUCCESS = 'success';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';
}
