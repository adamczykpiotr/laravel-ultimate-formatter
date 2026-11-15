<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

enum ConfigChangeType: string
{
    case ADDED = 'ADDED';
    case REMOVED = 'REMOVED';
    case RENAMED = 'RENAMED';
}
