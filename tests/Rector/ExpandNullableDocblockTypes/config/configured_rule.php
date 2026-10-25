<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ExpandNullableDocblockTypesRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([ExpandNullableDocblockTypesRector::class]);
