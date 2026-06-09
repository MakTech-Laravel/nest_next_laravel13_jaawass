<?php

namespace App\Enums;

enum TicketDepartmentType: string
{
    case General = 'general';
    case Sales = 'sales';
    case Technical = 'technical';
    case Billing = 'billing';
    case Account = 'account';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Sales => 'Sales',
            self::Technical => 'Technical',
            self::Billing => 'Billing',
            self::Account => 'Account',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
