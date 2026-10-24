<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\NormalizeMethodDocblockRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([NormalizeMethodDocblockRector::class]);
