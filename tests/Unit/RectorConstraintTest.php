<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use Composer\Semver\Semver;
use PHPUnit\Framework\TestCase;
use Rector\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\CallablePhpDocNodeVisitor;
use ReflectionMethod;

class RectorConstraintTest extends TestCase
{
    /**
     * @return void
     */
    public function test_requires_rector_that_builds_callable_visitor_from_callable_alone(): void
    {
        $composer = json_decode((string)file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($composer);
        $require = $composer['require'] ?? null;
        $this->assertIsArray($require);
        $constraint = $require['rector/rector'] ?? null;
        $this->assertIsString($constraint);

        // NormalizeMethodDocblockRector passes CallablePhpDocNodeVisitor only a callable; 2.8.0 and 2.8.1 also require the doc content.
        $this->assertFalse(Semver::satisfies('2.8.1', $constraint));
        $this->assertTrue(Semver::satisfies('2.8.2', $constraint));
    }

    /**
     * @return void
     */
    public function test_installed_rector_builds_callable_visitor_from_callable_alone(): void
    {
        $constructor = new ReflectionMethod(CallablePhpDocNodeVisitor::class, '__construct');

        $this->assertSame(1, $constructor->getNumberOfRequiredParameters());
    }
}
