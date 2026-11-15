<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Config\ConfigMigrator;
use AdamczykPiotr\UltimateFormatter\Console\Commands\MigrateConfigCommand;
use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MigrateConfigCommandTest extends TestCase
{
    protected const DEFAULT_CONFIG = __DIR__ . '/../../config/ultimate-formatter.php';
    protected const CONFIG = "<?php\n\nreturn [\n    'format' => [\n        'rector' => ['process'],\n    ],\n];\n";
    protected const DIFF = "--- a/config/ultimate-formatter.php\n+++ b/config/ultimate-formatter.php\n@@ -2,3 +2,2 @@\n     'format' => [\n-        'rector' => ['process'],\n     ],\n";

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
            "Removed rector.\nMigrated config/ultimate-formatter.php from version 0.0.0 to 1.0.0.\n",
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

        $this->assertStringContainsString('Would migrate config/ultimate-formatter.php from version 0.0.0 to 1.0.0.', $tester->getDisplay());
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
            "Skipped the config migration, config/ultimate-formatter.php can't be edited: it does not return a [...] array.",
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
     * @return void
     */
    public function test_migrates_a_config_published_before_config_version_to_the_default(): void
    {
        $configFile = "{$this->projectPath}/config/ultimate-formatter.php";
        $this->filesystem->copy(__DIR__ . '/Fixture/ultimate-formatter-1.1.0.php.inc', $configFile);

        $tester = new CommandTester(new MigrateConfigCommand());
        $tester->execute(['--path' => $this->projectPath]);

        $this->assertSame($this->withoutVersion($this->filesystem->readFile(self::DEFAULT_CONFIG)), $this->withoutVersion($this->filesystem->readFile($configFile)));
        $this->assertStringContainsString('Migrated config/ultimate-formatter.php from version 1.1.0', $tester->getDisplay());
        $this->assertStringNotContainsString('Skipped', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function test_leaves_an_unversioned_current_config_unchanged_apart_from_the_version(): void
    {
        $configFile = "{$this->projectPath}/config/ultimate-formatter.php";
        $this->filesystem->dumpFile($configFile, $this->withoutVersion($this->filesystem->readFile(self::DEFAULT_CONFIG)));

        $tester = new CommandTester(new MigrateConfigCommand());
        $tester->execute(['--path' => $this->projectPath], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertSame($this->withoutVersion($this->filesystem->readFile(self::DEFAULT_CONFIG)), $this->withoutVersion($this->filesystem->readFile($configFile)));
        $this->assertStringContainsString("'config-version' =>", $this->filesystem->readFile($configFile));
        $this->assertStringNotContainsString('Added', $tester->getDisplay());
    }

    /**
     * @param string $config
     *
     * @return string
     */
    protected function withoutVersion(string $config): string
    {
        return Str::of($config)->replaceMatches("/^    'config-version' => '[^']*',\n\n/m", '')->toString();
    }

    /**
     * @param array<string, bool> $options
     *
     * @return CommandTester
     */
    protected function runMigrateConfig(array $options = []): CommandTester
    {
        $this->filesystem->dumpFile("{$this->projectPath}/diffs/1.0.0.diff", self::DIFF);
        $migrator = new ConfigMigrator("{$this->projectPath}/diffs");
        $tester = new CommandTester(new MigrateConfigCommand($migrator));
        $tester->execute(['--path' => $this->projectPath, ...$options]);

        return $tester;
    }
}
