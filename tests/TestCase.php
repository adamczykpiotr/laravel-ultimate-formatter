<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests;

use AdamczykPiotr\UltimateFormatter\UltimateFormatterServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @param Application $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            UltimateFormatterServiceProvider::class,
        ];
    }
}
