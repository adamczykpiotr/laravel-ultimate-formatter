<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ArrayKeyExistsTernaryToCoalesceRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([ArrayKeyExistsTernaryToCoalesceRector::class]);
