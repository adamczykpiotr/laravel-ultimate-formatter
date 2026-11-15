<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

enum ConfigMigrationOutcome
{
    case UP_TO_DATE;
    case MIGRATED;
}
