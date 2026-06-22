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
            self::PENDING => __('review.status.pending'),
            self::PUBLISHED => __('review.status.published'),
            self::HIDDEN => __('review.status.hidden'),
            self::FLAGGED => __('review.status.flagged'),
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function optionMap(): array
    {
        return collect(self::options())
            ->pluck('label', 'value')
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function publicValues(): array
    {
        return [self::PUBLISHED->value];
    }
}
