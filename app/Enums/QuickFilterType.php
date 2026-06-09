<?php

namespace App\Enums;

enum QuickFilterType: string
{
    case Countries = 'countries';
    case Certifications = 'certifications';
    case MoqRanges = 'moq_ranges';
    case ExportMarkets = 'export_markets';

    public function label(): string
    {
        return match ($this) {
            self::Countries => 'Countries',
            self::Certifications => 'Certifications',
            self::MoqRanges => 'MOQ Ranges',
            self::ExportMarkets => 'Export Markets',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }

    /** Distinct filter kinds (for summary cards). */
    public static function definedTypeCount(): int
    {
        return count(self::cases());
    }
}
