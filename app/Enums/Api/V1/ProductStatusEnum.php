<?php

namespace App\Enums\Api\V1;

enum ProductStatusEnum: string
{
    //
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DRAFT = 'draft';


    public function option(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DRAFT => 'Draft',
        };
    }
}
