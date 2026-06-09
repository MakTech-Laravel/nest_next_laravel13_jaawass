<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case PENDING = 'pending';
    case VALID = 'valid';
    case EXPIRED = 'expired';
    case EXPIRING_SOON = 'expiring_soon';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Waiting for verification',
            self::VALID => 'Valid',
            self::EXPIRED => 'Expired',
            self::EXPIRING_SOON => 'Expiring Soon',
        };
    }

    public static function ctaOptions(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::VALID->value => self::VALID->label(),
            self::EXPIRED->value => self::EXPIRED->label(),
            self::EXPIRING_SOON->value => self::EXPIRING_SOON->label(),
        ];
    }

    public function isValid(): bool
    {
        return $this === self::VALID;
    }

    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    public function isExpiringSoon(): bool
    {
        return $this === self::EXPIRING_SOON;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
