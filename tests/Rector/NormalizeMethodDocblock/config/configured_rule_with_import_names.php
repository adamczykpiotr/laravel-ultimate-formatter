<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\NormalizeMethodDocblockRector;
use Rector\Config\RectorConfig;

// Mirrors the name importing in RectorConfigFactory, which shortens the fully qualified names the rule prints.
return RectorConfig::configure()
    ->withImportNames(
        importShortClasses: true,
        removeUnusedImports: true,
    )
    ->withRules([NormalizeMethodDocblockRector::class]);
