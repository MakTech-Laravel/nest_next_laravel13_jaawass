<?php

namespace App\Enums;

enum AdditionalInformationRequestStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::Expired => 'Expired',
        };
    }
}
