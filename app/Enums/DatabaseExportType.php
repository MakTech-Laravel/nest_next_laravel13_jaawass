<?php

namespace App\Enums;

enum DatabaseExportType: string
{
    case Backup = 'backup';
    case Export = 'export';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
