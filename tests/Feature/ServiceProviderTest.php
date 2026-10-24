<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ServiceProviderTest extends TestCase
{
    /**
     * @return void
     */
    public function test_registers_package_config(): void
    {
        $this->assertIsArray(config('ultimate-formatter'));
    }

    /**
     * @return void
     */
    public function test_registers_artisan_commands(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('formatter:install', $commands);
        $this->assertArrayHasKey('formatter:format', $commands);
        $this->assertArrayHasKey('formatter:check', $commands);
    }
}
