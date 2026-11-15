<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\GroupTraitUsesRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([GroupTraitUsesRector::class]);
