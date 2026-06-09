<?php

namespace App\Enums\Api\V1;

enum CatalogStatusEnum: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
