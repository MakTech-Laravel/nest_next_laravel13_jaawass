<?php

namespace App\DTO\Payment;

readonly class VerifiedPaymentDTO
{
    public function __construct(
        public string $externalId,
        public float $amount,
        public string $currency,
        public string $status,
        public string $paymentMethod,
        public ?string $vaultId = null,
        public ?string $payerId = null,
    ) {}
}
