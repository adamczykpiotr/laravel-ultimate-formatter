<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\RelationMethodReturnTypeRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([RelationMethodReturnTypeRector::class]);
