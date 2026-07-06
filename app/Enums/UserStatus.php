<?php

namespace App\Enums;

enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case DEACTIVATED = 'deactivated';
    case SUSPENDED = 'suspended';
    case SCHEDULED_DELETION = 'scheduled_deletion';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::DEACTIVATED => 'Deactivated',
            self::SUSPENDED => 'Suspended',
            self::SCHEDULED_DELETION => 'Scheduled deletion',
            self::DELETED => 'Deleted',
        };
    }

    public static function ctaOptions(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::ACTIVE->value => self::ACTIVE->label(),
            self::DEACTIVATED->value => self::DEACTIVATED->label(),
            self::SUSPENDED->value => self::SUSPENDED->label(),
            self::SCHEDULED_DELETION->value => self::SCHEDULED_DELETION->label(),
            self::DELETED->value => self::DELETED->label(),
        ];
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isDeactivated(): bool
    {
        return $this === self::DEACTIVATED;
    }

    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    public function isScheduledDeletion(): bool
    {
        return $this === self::SCHEDULED_DELETION;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
