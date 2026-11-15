<?php

declare(strict_types=1);

namespace App\Enums;

enum ExternalTagState: string
{
    case Open = 'open';
    case CLOSED = 'closed-by-vendor';
}
