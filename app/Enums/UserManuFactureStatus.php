<?php

namespace App\Enums;

enum UserManuFactureStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function allowsApiLogin(): bool
    {
        return $this === self::APPROVED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Legacy manufacturer rows may have a null status; treat as pending until reviewed.
     */
    public static function normalizedForManufacturer(?self $status): self
    {
        return $status ?? self::PENDING;
    }
}
