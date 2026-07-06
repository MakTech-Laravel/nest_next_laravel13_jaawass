<?php

namespace App\Enums;

enum AdditionalInformationRequestStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }
}
