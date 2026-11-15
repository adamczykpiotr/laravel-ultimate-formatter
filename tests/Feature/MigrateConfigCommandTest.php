<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Config\ConfigChange;
use AdamczykPiotr\UltimateFormatter\Config\ConfigMigrator;
use AdamczykPiotr\UltimateFormatter\Console\Commands\MigrateConfigCommand;
use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MigrateConfigCommandTest extends TestCase
{
    protected const CONFIG = "<?php\n\nreturn [\n    'format' => [\n        'rector' => ['process'],\n    ],\n];\n";

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
    public function test_migrates_the_published_config(): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/config/ultimate-formatter.php", self::CONFIG);

        $tester = $this->runMigrateConfig();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(
            "Removed format.rector.\nMigrated config/ultimate-formatter.php from version 0 to 1.\n",
            $tester->getDisplay(),
        );
        $this->assertStringNotContainsString("'rector'", $this->filesystem->readFile("{$this->projectPath}/config/ultimate-formatter.php"));
    }

    /**
     * @return void
     */
    public function test_dry_run_prints_the_changes_only(): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/config/ultimate-formatter.php", self::CONFIG);

        $tester = $this->runMigrateConfig(['--dry-run' => true]);

        $this->assertStringContainsString('Would migrate config/ultimate-formatter.php from version 0 to 1.', $tester->getDisplay());
        $this->assertSame(self::CONFIG, $this->filesystem->readFile("{$this->projectPath}/config/ultimate-formatter.php"));
    }

    /**
     * @return void
     */
    public function test_succeeds_without_a_published_config(): void
    {
        $tester = $this->runMigrateConfig();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame('', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function test_warns_about_a_config_it_cannot_edit_without_failing(): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/config/ultimate-formatter.php", "<?php\n\nreturn require 'shared.php';\n");

        $tester = $this->runMigrateConfig();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString(
            "Skipped the config migration, config/ultimate-formatter.php can't be edited: it does not return a literal [...] array.",
            $tester->getDisplay(),
        );
    }

    /**
     * @return void
     */
    public function test_migrates_the_config_install_publishes_to_the_latest_version(): void
    {
        $this->filesystem->copy(__DIR__ . '/../../config/ultimate-formatter.php', "{$this->projectPath}/config/ultimate-formatter.php");

        $tester = new CommandTester(new MigrateConfigCommand());
        $tester->execute(['--path' => $this->projectPath]);

        $this->assertSame('', $tester->getDisplay());
        $this->assertFileEquals(__DIR__ . '/../../config/ultimate-formatter.php', "{$this->projectPath}/config/ultimate-formatter.php");
    }

    /**
     * @param array<string, bool> $options
     *
     * @return CommandTester
     */
    protected function runMigrateConfig(array $options = []): CommandTester
    {
        $migrator = new ConfigMigrator([1 => [ConfigChange::removed(['format', 'rector'])]]);
        $tester = new CommandTester(new MigrateConfigCommand($migrator));
        $tester->execute(['--path' => $this->projectPath, ...$options]);

        return $tester;
    }
}
