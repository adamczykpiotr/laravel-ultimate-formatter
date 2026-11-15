<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ExplicitNullFalseComparisonRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([ExplicitNullFalseComparisonRector::class]);
