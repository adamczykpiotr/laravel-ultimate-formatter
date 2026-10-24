<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExpandNullableDocblockTypes;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ExpandNullableDocblockTypesRectorTest extends AbstractRectorTestCase
{
    /**
     * @param string $filePath
     * @return void
     */
    #[DataProvider('provideFixtures')]
    public function test_refactors_fixture(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    /**
     * @return Iterator<array<int, string>>
     */
    public static function provideFixtures(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    /**
     * @return string
     */
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
