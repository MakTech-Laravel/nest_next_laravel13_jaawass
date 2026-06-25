<?php

namespace App\Enums;

enum SupplierReportReason: string
{
    case Fake = 'fake';
    case Scam = 'scam';
    case Quality = 'quality';
    case Communication = 'communication';
    case Certification = 'certification';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fake => 'Fake or fraudulent supplier',
            self::Scam => 'Scam or misleading information',
            self::Quality => 'Poor quality or defective products',
            self::Communication => 'Unresponsive or unprofessional',
            self::Certification => 'False certification claims',
            self::Other => 'Other issue',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
