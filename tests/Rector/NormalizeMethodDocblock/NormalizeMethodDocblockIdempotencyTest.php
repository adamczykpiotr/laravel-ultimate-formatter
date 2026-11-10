<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\Fixture\FixtureSplitter;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symfony\Component\Filesystem\Filesystem;

class NormalizeMethodDocblockIdempotencyTest extends AbstractRectorTestCase
{
    /**
     * @var list<string>
     */
    protected array $createdPaths = [];

    /**
     * @param string $filePath
     *
     * @return void
     */
    #[DataProvider('provideFixtures')]
    public function test_second_run_reports_no_change(string $filePath): void
    {
        $this->doTestFile($filePath);
        // Rector removes only the input file of the last doTestFile() call, which is the second run's.
        $this->createdPaths[] = substr($filePath, 0, -strlen('.inc'));

        $contents = file_get_contents($filePath);
        $this->assertIsString($contents);
        [, $firstRunOutput] = FixtureSplitter::splitFixtureFileContents($contents);

        $secondRunDirectory = sys_get_temp_dir() . '/' . uniqid('normalize-method-docblock-', true);
        $this->createdPaths[] = $secondRunDirectory;
        $secondRunFixture = $secondRunDirectory . '/' . basename($filePath);
        (new Filesystem())->dumpFile($secondRunFixture, $firstRunOutput);

        // A fixture without a split expects unchanged output, and Rector echoes a warning if a rule still reported a change.
        ob_start();

        try {
            $this->doTestFile($secondRunFixture);
        } finally {
            $warning = ob_get_clean();
        }

        $this->assertSame('', $warning);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove($this->createdPaths);
        $this->createdPaths = [];
    }

    /**
     * @return Iterator<array<int, string>>
     */
    public static function provideFixtures(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/IdempotencyFixture');
    }

    /**
     * @return string
     */
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule_with_import_names.php';
    }
}
