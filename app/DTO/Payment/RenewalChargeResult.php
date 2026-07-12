<?php

namespace App\DTO\Payment;

use App\Enums\Api\V1\Payment\RenewalChargeOutcome;

readonly class RenewalChargeResult
{
    public function __construct(
        public RenewalChargeOutcome $outcome,
        public ?string $externalId = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $message = null,
    ) {}

    public static function success(string $externalId, float $amount, string $currency): self
    {
        return new self(
            outcome: RenewalChargeOutcome::SUCCESS,
            externalId: $externalId,
            amount: $amount,
            currency: $currency,
        );
    }

    public static function skipped(string $message): self
    {
        return new self(
            outcome: RenewalChargeOutcome::SKIPPED,
            message: $message,
        );
    }

    public static function failed(string $message): self
    {
        return new self(
            outcome: RenewalChargeOutcome::FAILED,
            message: $message,
        );
    }

    public function isSuccess(): bool
    {
        return $this->outcome === RenewalChargeOutcome::SUCCESS;
    }

    public function isSkipped(): bool
    {
        return $this->outcome === RenewalChargeOutcome::SKIPPED;
    }

    public function isFailed(): bool
    {
        return $this->outcome === RenewalChargeOutcome::FAILED;
    }
}
