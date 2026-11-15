<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ForeachNestedIfsToEarlyContinueRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([ForeachNestedIfsToEarlyContinueRector::class]);
