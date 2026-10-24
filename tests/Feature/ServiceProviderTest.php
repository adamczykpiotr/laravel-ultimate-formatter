<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    /**
     * @return void
     */
    public function test_registers_package_config(): void
    {
        $this->assertIsArray(config('ultimate-formatter'));
    }
}
