<?php

declare(strict_types=1);

namespace App\Enums;

enum TagPriority: int
{
    case LOW = 1;
    case HIGH_2 = 2;
    case urgent = 3;
}
