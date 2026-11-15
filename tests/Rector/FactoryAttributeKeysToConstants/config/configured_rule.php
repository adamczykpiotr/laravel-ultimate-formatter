<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\FactoryAttributeKeysToConstantsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([FactoryAttributeKeysToConstantsRector::class]);
