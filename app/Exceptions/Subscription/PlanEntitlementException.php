<?php

namespace App\Exceptions\Subscription;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class PlanEntitlementException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly ?string $featureKey = null,
        public readonly ?int $limit = null,
        public readonly int $statusCode = Response::HTTP_FORBIDDEN,
    ) {
        parent::__construct($message);
    }

    public static function noActiveSubscription(): self
    {
        return new self(
            message: __('subscription.no_active_subscription'),
            errorCode: 'no_active_subscription',
        );
    }

    public static function featureNotAvailable(string $featureKey): self
    {
        return new self(
            message: __('subscription.feature_not_available', [
                'feature' => __('subscription.features.'.$featureKey),
            ]),
            errorCode: 'feature_not_available',
            featureKey: $featureKey,
        );
    }

    public static function limitExceeded(string $featureKey, int $limit): self
    {
        return new self(
            message: __('subscription.limit_exceeded', [
                'feature' => __('subscription.features.'.$featureKey),
                'limit' => $limit,
            ]),
            errorCode: 'limit_exceeded',
            featureKey: $featureKey,
            limit: $limit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return array_filter([
            'code' => $this->errorCode,
            'feature' => $this->featureKey,
            'limit' => $this->limit,
        ], fn ($value) => $value !== null);
    }
}
