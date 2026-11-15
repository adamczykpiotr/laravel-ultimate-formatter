<?php

declare(strict_types=1);

use AdamczykPiotr\UltimateFormatter\Rector\RectorConfigFactory;

$builder = RectorConfigFactory::create()
    ->withBootstrapFiles([__DIR__ . '/../stubs/laravel-data.php']);

// spatie/laravel-data isn't installed here, so register the rules that depend on it by hand.
foreach (RectorConfigFactory::configuredRules(laravelDataInstalled: true) as $rule => $configuration) {
    $builder->withConfiguredRule($rule, $configuration);
}

return $builder;
