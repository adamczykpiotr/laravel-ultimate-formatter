<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\RenameGenericForeachValueVariableRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([RenameGenericForeachValueVariableRector::class]);
