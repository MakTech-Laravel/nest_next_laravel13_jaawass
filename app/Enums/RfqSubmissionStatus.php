<?php

namespace App\Enums;

enum RfqSubmissionStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Expired = 'expired';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
