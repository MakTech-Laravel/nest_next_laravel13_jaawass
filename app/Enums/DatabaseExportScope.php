<?php

namespace App\Enums;

enum DatabaseExportScope: string
{
    case Full = 'full';
    case Tables = 'tables';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
