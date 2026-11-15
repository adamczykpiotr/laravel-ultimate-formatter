<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Drift;

use PHPUnit\Framework\TestCase;

/**
 * Guards against upstream drift: Rector, PHP CS Fixer and PHPStan change rule behaviour in minor releases.
 * `composer fixtures:update` rewrites the expected output instead of comparing against it.
 */
abstract class DriftTestCase extends TestCase
{
    protected const ROOT_DIRECTORY = __DIR__ . '/../..';
    protected const UPDATE_ENV_VARIABLE = 'UPDATE_FIXTURES';

    /**
     * @return bool
     */
    protected function updatesFixtures(): bool
    {
        return getenv(self::UPDATE_ENV_VARIABLE) === '1';
    }
}
