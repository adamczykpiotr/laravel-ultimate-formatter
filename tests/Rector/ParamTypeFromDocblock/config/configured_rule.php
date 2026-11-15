<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ParamTypeFromDocblockRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([ParamTypeFromDocblockRector::class]);
