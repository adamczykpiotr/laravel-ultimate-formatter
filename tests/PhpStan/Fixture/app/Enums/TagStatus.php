<?php

declare(strict_types=1);

namespace App\Enums;

enum TagStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PENDING_REVIEW = 'pending_review';
    case Archived = 'Archived';
}
