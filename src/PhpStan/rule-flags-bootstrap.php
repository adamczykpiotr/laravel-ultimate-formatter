<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\PhpStan\RuleFlags;
use PHPStan\DependencyInjection\Container;

// PHPStan runs bootstrap files with its container in scope. Building RuleFlags here, before the analysis,
// makes a wrong flag stop the run with its message instead of an internal error while analysing a file.
if (isset($container) && $container instanceof Container) {
    $container->getByType(RuleFlags::class);
}
