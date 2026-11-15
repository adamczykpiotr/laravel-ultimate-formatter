<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\Config\ConfigMigrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ConfigMigratorTest extends TestCase
{
    protected const ROOT_DIRECTORY = __DIR__ . '/../..';
    protected const GENERATOR_SCRIPT = self::ROOT_DIRECTORY . '/.github/scripts/generate-config-diff.sh';
    protected const OLD_DEFAULT = <<<'PHP'
        <?php

        declare(strict_types=1);

        return [
            'config-version' => '0.0.0',

            'format' => [
                'rector' => ['process', '--no-diffs'],
                'php-cs-fixer' => ['fix'],
            ],
            'check' => [
                'rector' => ['process', '--dry-run'],
                'php-cs-fixer' => ['fix', '--dry-run'],
                'phpstan' => ['analyse'],
            ],
            'phpstan-rules' => [
                /*
                | Banned calls
                */

                'ultimateFormatter.disallowSprintf' => true,
                'ultimateFormatter.disallowEnv' => true,
                'ultimateFormatter.disallowOld' => true,
            ],
        ];

        PHP;
    protected const NEW_DEFAULT = <<<'PHP'
        <?php

        declare(strict_types=1);

        return [
            'config-version' => '0.0.0',

            'format' => [
                'rector' => ['process', '--no-diffs'],
                'php-cs-fixer' => ['fix'],
            ],
            'check' => [
                'rector' => ['process', '--dry-run'],
                'php-cs-fixer' => ['fix', '--dry-run'],
                'phpstan' => ['analyse', '--memory-limit=2G'],
            ],
            'phpstan-rules' => [
                /*
                | Banned calls
                */

                'ultimateFormatter.disallowSprintf' => true,
                'ultimateFormatter.disallowEnvCalls' => true,
                'ultimateFormatter.disallowDd' => true,
            ],
        ];

        PHP;

    protected Filesystem $filesystem;
    protected string $packagePath;
    protected string $configFile;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->packagePath = $this->filesystem->tempnam(sys_get_temp_dir(), 'ultimate-formatter-package-');
        $this->filesystem->remove($this->packagePath);
        $this->filesystem->mkdir($this->packagePath);
        $this->configFile = "{$this->packagePath}/published.php";

        $this->filesystem->dumpFile("{$this->packagePath}/config/ultimate-formatter.php", self::OLD_DEFAULT);
        $this->filesystem->dumpFile("{$this->packagePath}/config/diffs/.gitkeep", '');
        $this->git('init', '--quiet');
        $this->commit();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->packagePath);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_default_config_includes_every_diff(): void
    {
        $config = require self::ROOT_DIRECTORY . '/config/ultimate-formatter.php';

        $this->assertIsArray($config);
        $this->assertIsString($config['config-version']);
        $this->assertTrue(version_compare($config['config-version'], (new ConfigMigrator())->latestVersion(), '>='));
    }

    /**
     * @return void
     */
    public function test_generator_skips_an_unchanged_config(): void
    {
        $output = $this->generateDiff();

        $this->assertStringContainsString('config/ultimate-formatter.php is unchanged', $output);
        $this->assertSame('0.0.0', $this->migrator()->latestVersion());
        $this->assertStringContainsString("'config-version' => '1.0.0',", $this->filesystem->readFile("{$this->packagePath}/config/ultimate-formatter.php"));
    }

    /**
     * @return void
     */
    public function test_generator_records_the_change_and_bumps_the_version(): void
    {
        $this->releaseNewDefault();

        $this->assertSame('1.0.0', $this->migrator()->latestVersion());
        $this->assertStringContainsString("'config-version' => '1.0.0',", $this->filesystem->readFile("{$this->packagePath}/config/ultimate-formatter.php"));
        $this->assertStringNotContainsString('config-version', $this->filesystem->readFile("{$this->packagePath}/config/diffs/1.0.0.diff"));
        $this->assertStringContainsString('is unchanged', $this->generateDiff('1.0.1'));
    }

    /**
     * @return void
     */
    public function test_orders_diffs_by_version(): void
    {
        $this->filesystem->dumpFile("{$this->packagePath}/config/diffs/1.9.0.diff", '');
        $this->filesystem->dumpFile("{$this->packagePath}/config/diffs/1.10.0.diff", '');

        $this->assertSame('1.10.0', $this->migrator()->latestVersion());
    }

    /**
     * @return void
     */
    public function test_migrates_an_untouched_config_to_the_new_default(): void
    {
        $this->releaseNewDefault();
        $this->filesystem->dumpFile($this->configFile, self::OLD_DEFAULT);

        $migration = $this->migrator()->migrate($this->configFile);

        $this->assertSame($this->filesystem->readFile("{$this->packagePath}/config/ultimate-formatter.php"), $this->filesystem->readFile($this->configFile));
        $this->assertSame([
            'Updated the default of phpstan.',
            'Removed ultimateFormatter.disallowEnv.',
            'Added ultimateFormatter.disallowEnvCalls.',
            'Removed ultimateFormatter.disallowOld.',
            'Added ultimateFormatter.disallowDd.',
        ], $migration->applied);
        $this->assertSame([], $migration->skipped);
    }

    /**
     * @return void
     */
    public function test_keeps_the_projects_values_and_comments(): void
    {
        $this->releaseNewDefault();
        $this->filesystem->dumpFile($this->configFile, str_replace(
            ["'phpstan' => ['analyse'],", "'ultimateFormatter.disallowEnv' => true,", "'ultimateFormatter.disallowSprintf' => true,"],
            ["'phpstan' => ['analyse', '--memory-limit=4G'],", "'ultimateFormatter.disallowEnv' => false, // legacy module", "// Turned off until the reports move to Str.\n        'ultimateFormatter.disallowSprintf' => false,"],
            self::OLD_DEFAULT,
        ));

        $migration = $this->migrator()->migrate($this->configFile);

        $published = $this->filesystem->readFile($this->configFile);
        $this->assertStringContainsString("'phpstan' => ['analyse', '--memory-limit=4G'],", $published);
        $this->assertStringContainsString("// Turned off until the reports move to Str.\n        'ultimateFormatter.disallowSprintf' => false,", $published);
        $this->assertStringContainsString("'ultimateFormatter.disallowEnvCalls' => true,\n        'ultimateFormatter.disallowDd' => true,\n    ],", $published);
        $this->assertStringNotContainsString('disallowOld', $published);
        $this->assertContains("Kept your value of phpstan, its default is now ['analyse', '--memory-limit=2G'].", $migration->applied);
        $this->assertContains('Removed ultimateFormatter.disallowEnv, move your value false over if it still applies.', $migration->applied);
        $this->assertSame([], $migration->skipped);
    }

    /**
     * @return void
     */
    public function test_skips_a_change_whose_lines_were_removed(): void
    {
        $this->releaseNewDefault();
        $this->filesystem->dumpFile($this->configFile, str_replace("        'ultimateFormatter.disallowOld' => true,\n", '', self::OLD_DEFAULT));

        $migration = $this->migrator()->migrate($this->configFile);

        $this->assertSame(['Updated the default of phpstan.'], $migration->applied);
        $this->assertCount(1, $migration->skipped);
        $this->assertStringStartsWith('Skipped a change from config/diffs/1.0.0.diff, the lines around it were edited:', $migration->skipped[0]);
        $this->assertStringContainsString("-        'ultimateFormatter.disallowEnv' => true,", $migration->skipped[0]);
        $this->assertStringContainsString("'config-version' => '1.0.0',", $this->filesystem->readFile($this->configFile));
    }

    /**
     * @return void
     */
    public function test_adds_the_version_to_a_config_published_before_it(): void
    {
        $this->releaseNewDefault();
        $this->filesystem->dumpFile($this->configFile, str_replace("    'config-version' => '0.0.0',\n\n", '', self::OLD_DEFAULT));

        $this->migrator()->migrate($this->configFile);

        $this->assertSame($this->filesystem->readFile("{$this->packagePath}/config/ultimate-formatter.php"), $this->filesystem->readFile($this->configFile));
    }

    /**
     * @return void
     */
    public function test_dry_run_leaves_the_file_alone(): void
    {
        $this->releaseNewDefault();
        $this->filesystem->dumpFile($this->configFile, self::OLD_DEFAULT);

        $migration = $this->migrator()->migrate($this->configFile, dryRun: true);

        $this->assertSame('1.0.0', $migration->toVersion);
        $this->assertSame(self::OLD_DEFAULT, $this->filesystem->readFile($this->configFile));
    }

    /**
     * @return void
     */
    public function test_leaves_an_up_to_date_config_alone(): void
    {
        $this->releaseNewDefault();
        $published = str_replace("'config-version' => '0.0.0',", "'config-version' => '1.0.0',", self::OLD_DEFAULT);
        $this->filesystem->dumpFile($this->configFile, $published);

        $migration = $this->migrator()->migrate($this->configFile);

        $this->assertTrue($migration->isUpToDate());
        $this->assertSame($published, $this->filesystem->readFile($this->configFile));
    }

    /**
     * @return ConfigMigrator
     */
    protected function migrator(): ConfigMigrator
    {
        return new ConfigMigrator("{$this->packagePath}/config/diffs");
    }

    /**
     * @return void
     */
    protected function releaseNewDefault(): void
    {
        $this->filesystem->dumpFile("{$this->packagePath}/config/ultimate-formatter.php", self::NEW_DEFAULT);
        $this->commit();
        $this->generateDiff('1.0.0');
        $this->commit();
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function generateDiff(string $version = '1.0.0'): string
    {
        $process = new Process([self::GENERATOR_SCRIPT, $version], $this->packagePath);
        $process->mustRun();

        return $process->getOutput();
    }

    /**
     * @return void
     */
    protected function commit(): void
    {
        $this->git('add', '--all');
        $this->git('-c', 'user.name=Test', '-c', 'user.email=test@example.com', 'commit', '--quiet', '--allow-empty', '--message=change');
    }

    /**
     * @param string ...$arguments
     *
     * @return void
     */
    protected function git(string ...$arguments): void
    {
        (new Process(['git', ...$arguments], $this->packagePath))->mustRun();
    }
}
