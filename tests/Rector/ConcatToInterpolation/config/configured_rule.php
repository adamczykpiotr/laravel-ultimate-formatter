<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ConcatToInterpolationRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([ConcatToInterpolationRector::class]);
