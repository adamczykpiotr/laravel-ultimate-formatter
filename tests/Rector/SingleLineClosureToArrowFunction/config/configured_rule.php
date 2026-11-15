<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\SingleLineClosureToArrowFunctionRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([SingleLineClosureToArrowFunctionRector::class]);
