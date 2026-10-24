<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UltimateFormatterServiceProvider extends PackageServiceProvider
{
    /**
     * @param Package $package
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ultimate-formatter')
            ->hasConfigFile('ultimate-formatter');
    }
}
