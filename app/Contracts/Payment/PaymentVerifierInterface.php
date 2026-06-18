<?php

namespace App\Contracts\Payment;

use App\DTO\Payment\VerifiedPaymentDTO;

interface PaymentVerifierInterface
{
    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function verify(array $paymentData): VerifiedPaymentDTO;
}
