<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\Rector\IgnoreMarkerSkips;
use AdamczykPiotr\UltimateFormatter\Rector\Rules\GroupTraitUsesRector;
use PHPUnit\Framework\TestCase;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

class IgnoreMarkerSkipsTest extends TestCase
{
    protected Filesystem $filesystem;
    protected string $projectPath;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->projectPath = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-skips-', true);
        $this->filesystem->dumpFile("{$this->projectPath}/artisan", '');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectPath);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_skips_the_named_rules_for_the_marked_files(): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/app/Marked.php", implode("\n", [
            '<?php',
            '// formatter-ignore ChangeSwitchToMatchRector, GroupTraitUsesRector, array_syntax',
            'switch ($a) {}',
            '',
        ]));
        $this->filesystem->dumpFile("{$this->projectPath}/app/Other.php", "<?php\nswitch (\$a) {} // formatter-ignore ChangeSwitchToMatchRector\n");
        $this->filesystem->dumpFile("{$this->projectPath}/app/Plain.php", "<?php\nswitch (\$a) {}\n");

        $skips = IgnoreMarkerSkips::find($this->projectPath);
        $path = realpath($this->projectPath);

        $this->assertEqualsCanonicalizing([ChangeSwitchToMatchRector::class, GroupTraitUsesRector::class], array_keys($skips));
        $this->assertEqualsCanonicalizing(["{$path}/app/Marked.php", "{$path}/app/Other.php"], $skips[ChangeSwitchToMatchRector::class]);
        $this->assertSame(["{$path}/app/Marked.php"], $skips[GroupTraitUsesRector::class]);
    }

    /**
     * @return void
     */
    public function test_an_unknown_rule_fails(): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/app/Marked.php", "<?php\n\$a = 1; // formatter-ignore ChangeSwitchToMatchesRector\n");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('names an unknown Rector rule: ChangeSwitchToMatchesRector.');

        IgnoreMarkerSkips::find($this->projectPath);
    }

    /**
     * @return void
     */
    public function test_finds_nothing_outside_a_known_layout(): void
    {
        $this->assertSame([], IgnoreMarkerSkips::find("{$this->projectPath}/missing"));
    }
}
