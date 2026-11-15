<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Console\Commands\CheckCommand;
use AdamczykPiotr\UltimateFormatter\Console\Commands\FormatCommand;
use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\OutputInterface;
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

        $this->filesystem->dumpFile("{$this->projectPath}/artisan", <<<'PHP'
            <?php
            $arguments = array_slice($argv, 1);
            file_put_contents(__DIR__ . '/artisan.log', implode(' ', $arguments) . "\n", FILE_APPEND);
            exit($arguments[0] === @file_get_contents(__DIR__ . '/fail-on.txt') ? 2 : 0);
            PHP);
        $this->filesystem->dumpFile("{$this->projectPath}/vendor/bin/rector", <<<'PHP'
            <?php
            file_put_contents(__DIR__ . '/../../rector.log', implode(' ', array_slice($argv, 1)) . "\n", FILE_APPEND);
            echo "rector output\n";
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
        $this->publishConfig("['pre' => ['clear-cache' => true, 'migrate' => true]]");

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
     * @return array<string, array{int}>
     */
    public static function quietVerbosities(): array
    {
        return [
            'quiet' => [OutputInterface::VERBOSITY_QUIET],
            'silent' => [OutputInterface::VERBOSITY_SILENT],
        ];
    }

    /**
     * @param int $verbosity
     *
     * @return void
     */
    #[DataProvider('quietVerbosities')]
    public function test_quiet_silences_every_step(int $verbosity): void
    {
        $this->publishConfig("['pre' => ['migrate' => true]]");

        $tester = new CommandTester(new FormatCommand());
        $tester->execute(['--path' => $this->projectPath, '--models' => true], ['verbosity' => $verbosity]);

        $this->assertSame(
            "migrate --force --quiet\nide-helper:models --reset --write --quiet\n",
            $this->filesystem->readFile("{$this->projectPath}/artisan.log"),
        );
        $this->assertSame("process --no-progress-bar --no-diffs\n", $this->filesystem->readFile("{$this->projectPath}/rector.log"));
        $this->assertSame('', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function test_verbose_passes_its_level_on_and_prints_each_command(): void
    {
        $this->publishConfig("['pre' => ['clear-cache' => true]]");

        $tester = new CommandTester(new FormatCommand());
        $tester->execute(['--path' => $this->projectPath, '--models' => true], ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]);

        $this->assertSame(
            "optimize:clear -vv\nide-helper:models --reset --write -vv\n",
            $this->filesystem->readFile("{$this->projectPath}/artisan.log"),
        );
        $this->assertStringContainsString('> php artisan ide-helper:models --reset --write -vv', $tester->getDisplay());
        $this->assertSame("process --no-progress-bar --no-diffs\n", $this->filesystem->readFile("{$this->projectPath}/rector.log"));
        $this->assertStringContainsString('rector output', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function test_debug_runs_rector_with_its_debug_flag(): void
    {
        $this->publishConfig("['format' => ['rector' => ['process', '-v', '--quiet']]]");

        $tester = new CommandTester(new FormatCommand());
        $tester->execute(['--path' => $this->projectPath], ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertSame("process --debug\n", $this->filesystem->readFile("{$this->projectPath}/rector.log"));
        $this->assertStringContainsString('> php vendor/bin/rector process --debug', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function test_check_never_runs_pre_steps(): void
    {
        $this->publishConfig("['pre' => ['clear-cache' => true, 'migrate' => true]]");

        $tester = new CommandTester(new CheckCommand());
        $tester->execute(['--path' => $this->projectPath]);

        $this->assertFileDoesNotExist("{$this->projectPath}/artisan.log");
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
