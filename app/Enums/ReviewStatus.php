<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case HIDDEN = 'hidden';
    case FLAGGED = 'flagged';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::PUBLISHED => 'published',
            self::HIDDEN => 'hidden',
            self::FLAGGED => 'flagged',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::PUBLISHED->value => self::PUBLISHED->label(),
            self::HIDDEN->value => self::HIDDEN->label(),
            self::FLAGGED->value => self::FLAGGED->label(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function publicValues(): array
    {
        return [self::PUBLISHED->value];
    }
}
