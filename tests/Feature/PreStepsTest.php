<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Console\Commands\FormatCommand;
use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

class PreStepsTest extends TestCase
{
    protected string $projectPath;
    protected Filesystem $filesystem;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->projectPath = $this->filesystem->tempnam(sys_get_temp_dir(), 'ultimate-formatter-');
        $this->filesystem->remove($this->projectPath);
        $this->filesystem->mkdir($this->projectPath);

        // Logs each call and fails on the command named in fail-on.txt, standing in for the real artisan.
        $this->filesystem->dumpFile("{$this->projectPath}/artisan", <<<'PHP'
            <?php
            $arguments = array_slice($argv, 1);
            file_put_contents(__DIR__ . '/artisan.log', implode(' ', $arguments) . "\n", FILE_APPEND);
            exit($arguments[0] === @file_get_contents(__DIR__ . '/fail-on.txt') ? 2 : 0);
            PHP);
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
    public function test_runs_no_pre_steps_by_default(): void
    {
        $this->runFormat();

        $this->assertFileDoesNotExist("{$this->projectPath}/artisan.log");
    }

    /**
     * @return void
     */
    public function test_runs_enabled_pre_steps_before_ide_helper(): void
    {
        $this->publishConfig("['pre' => ['clear_cache' => true, 'migrate' => true]]");

        $this->runFormat(['--models' => true]);

        $this->assertSame(
            "optimize:clear --quiet\nmigrate --force\nide-helper:models --reset --write --quiet\n",
            $this->filesystem->readFile("{$this->projectPath}/artisan.log"),
        );
    }

    /**
     * @return void
     */
    public function test_disabled_steps_are_skipped(): void
    {
        $this->publishConfig("['pre' => ['migrate' => true]]");

        $this->runFormat();

        $this->assertSame("migrate --force\n", $this->filesystem->readFile("{$this->projectPath}/artisan.log"));
    }

    /**
     * @return void
     */
    public function test_stops_at_a_failing_step(): void
    {
        $this->publishConfig("['pre' => ['migrate' => true]]");
        $this->filesystem->dumpFile("{$this->projectPath}/fail-on.txt", 'migrate');

        $exitCode = $this->runFormat(['--models' => true]);

        $this->assertSame(2, $exitCode);
        $this->assertSame("migrate --force\n", $this->filesystem->readFile("{$this->projectPath}/artisan.log"));
    }

    /**
     * @return void
     */
    public function test_rejects_a_non_boolean_toggle(): void
    {
        $this->publishConfig("['pre' => ['migrate' => 'yes']]");

        $this->expectException(UnexpectedValueException::class);

        $this->runFormat();
    }

    /**
     * @param string $config
     *
     * @return void
     */
    protected function publishConfig(string $config): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/config/ultimate-formatter.php", "<?php\n\nreturn {$config};\n");
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return int
     */
    protected function runFormat(array $options = []): int
    {
        $tester = new CommandTester(new FormatCommand());

        return $tester->execute(['--path' => $this->projectPath, ...$options]);
    }
}
