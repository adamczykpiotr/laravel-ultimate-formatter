<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\Config\ConfigChange;
use AdamczykPiotr\UltimateFormatter\Config\ConfigMigrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

class ConfigMigratorTest extends TestCase
{
    protected const DEFAULT_CONFIG_FILE = __DIR__ . '/../../config/ultimate-formatter.php';
    protected const PUBLISHED_CONFIG = <<<'PHP'
        <?php

        declare(strict_types=1);

        return [
            'format' => [
                'rector' => ['process'], // runs first
                'php-cs-fixer' => ['fix'],
            ],
            // The ruleset's flags.
            'phpstan-rules' => [
                /*
                | Banned calls
                */

                'ultimateFormatter.disallowSprintf' => false,
                'ultimateFormatter.disallowOld' => false, // turned off for the legacy module

                /*
                | Architecture
                */

                'ultimateFormatter.controllerSuffix' => true
            ],
        ];

        PHP;

    protected string $configFile;
    protected Filesystem $filesystem;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->configFile = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-migrate-', true) . '.php';
        $this->filesystem->dumpFile($this->configFile, self::PUBLISHED_CONFIG);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->configFile);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_default_config_is_on_the_latest_version(): void
    {
        $config = require self::DEFAULT_CONFIG_FILE;

        $this->assertIsArray($config);
        $this->assertSame((new ConfigMigrator())->latestVersion(), $config[ConfigMigrator::VERSION_KEY]);
    }

    /**
     * @return void
     */
    public function test_migrations_are_numbered_from_one(): void
    {
        $versions = array_keys(ConfigMigrator::migrations());

        $this->assertSame($versions === [] ? [] : range(1, count($versions)), $versions);
    }

    /**
     * @return void
     */
    public function test_edits_only_the_keys_a_migration_names(): void
    {
        $migration = (new ConfigMigrator([
            1 => [
                ConfigChange::added(['phpstan-rules', 'ultimateFormatter.disallowDd'], true, after: 'ultimateFormatter.disallowOld'),
                ConfigChange::removed(['format', 'rector']),
            ],
            2 => [
                ConfigChange::renamed(['phpstan-rules', 'ultimateFormatter.disallowSprintf'], 'ultimateFormatter.disallowFormat'),
                ConfigChange::added(['phpstan-rules', 'ultimateFormatter.serviceSuffix'], true),
                ConfigChange::added(['format', 'pint'], ['--test', 'app']),
            ],
        ]))->migrate($this->configFile);

        $this->assertSame(<<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'format' => [
                    'php-cs-fixer' => ['fix'],
                    'pint' => ['--test', 'app'],
                ],
                // The ruleset's flags.
                'phpstan-rules' => [
                    /*
                    | Banned calls
                    */

                    'ultimateFormatter.disallowFormat' => false,
                    'ultimateFormatter.disallowOld' => false, // turned off for the legacy module
                    'ultimateFormatter.disallowDd' => true,

                    /*
                    | Architecture
                    */

                    'ultimateFormatter.controllerSuffix' => true,
                    'ultimateFormatter.serviceSuffix' => true,
                ],
                'config-version' => 2,
            ];

            PHP, $this->filesystem->readFile($this->configFile));
        $this->assertSame(0, $migration->fromVersion);
        $this->assertSame(2, $migration->toVersion);
        $this->assertSame([
            'Added phpstan-rules.ultimateFormatter.disallowDd.',
            'Removed format.rector.',
            'Renamed phpstan-rules.ultimateFormatter.disallowSprintf to phpstan-rules.ultimateFormatter.disallowFormat.',
            'Added phpstan-rules.ultimateFormatter.serviceSuffix.',
            'Added format.pint.',
        ], $migration->applied);
        $this->assertSame([], $migration->skipped);
    }

    /**
     * @return void
     */
    public function test_runs_each_migration_once(): void
    {
        (new ConfigMigrator([1 => [ConfigChange::removed(['format', 'rector'])]]))->migrate($this->configFile);
        $this->filesystem->dumpFile($this->configFile, str_replace("'php-cs-fixer'", "'rector' => ['process'],\n        'php-cs-fixer'", $this->filesystem->readFile($this->configFile)));

        $migration = (new ConfigMigrator([
            1 => [ConfigChange::removed(['format', 'rector'])],
            2 => [ConfigChange::removed(['format', 'php-cs-fixer'])],
        ]))->migrate($this->configFile);

        $config = require $this->configFile;
        $this->assertIsArray($config);
        $this->assertSame(['rector' => ['process']], $config['format']);
        $this->assertSame(2, $config['config-version']);
        $this->assertSame(['Removed format.php-cs-fixer.'], $migration->applied);
    }

    /**
     * @return void
     */
    public function test_leaves_an_up_to_date_config_alone(): void
    {
        $this->filesystem->dumpFile($this->configFile, "<?php\n\nreturn [\n    'config-version' => 1,\n];\n");

        $migration = (new ConfigMigrator([1 => [ConfigChange::added(['format'], [])]]))->migrate($this->configFile);

        $this->assertTrue($migration->isUpToDate());
        $this->assertSame("<?php\n\nreturn [\n    'config-version' => 1,\n];\n", $this->filesystem->readFile($this->configFile));
    }

    /**
     * @return void
     */
    public function test_skips_changes_the_project_already_made(): void
    {
        $migration = (new ConfigMigrator([1 => [
            ConfigChange::added(['phpstan-rules', 'ultimateFormatter.disallowOld'], true),
            ConfigChange::added(['pre', 'migrate'], true),
            ConfigChange::removed(['check', 'phpstan']),
            ConfigChange::renamed(['phpstan-rules', 'ultimateFormatter.missing'], 'ultimateFormatter.renamed'),
        ]]))->migrate($this->configFile);

        $this->assertSame([], $migration->applied);
        $this->assertSame([], $migration->skipped);
        $this->assertSame(
            str_replace("    ],\n];", "    ],\n    'config-version' => 1,\n];", self::PUBLISHED_CONFIG),
            $this->filesystem->readFile($this->configFile),
        );
    }

    /**
     * @return void
     */
    public function test_a_rename_keeps_the_new_key_the_project_already_set(): void
    {
        $migration = (new ConfigMigrator([1 => [
            ConfigChange::renamed(['phpstan-rules', 'ultimateFormatter.disallowOld'], 'ultimateFormatter.disallowSprintf'),
        ]]))->migrate($this->configFile);

        $config = require $this->configFile;
        $this->assertIsArray($config);
        $this->assertSame(['ultimateFormatter.disallowSprintf' => false, 'ultimateFormatter.controllerSuffix' => true], $config['phpstan-rules']);
        $this->assertSame(['Removed phpstan-rules.ultimateFormatter.disallowOld, phpstan-rules.ultimateFormatter.disallowSprintf already exists.'], $migration->applied);
    }

    /**
     * @return void
     */
    public function test_warns_and_skips_a_hand_edited_section(): void
    {
        $this->filesystem->dumpFile($this->configFile, <<<'PHP'
            <?php

            return [
                'phpstan-rules' => array_merge(require __DIR__ . '/shared-rules.php', [
                    'ultimateFormatter.disallowSprintf' => false,
                ]),
                'format' => [
                    'rector' => ['process'],
                ],
            ];

            PHP);

        $migration = (new ConfigMigrator([1 => [
            ConfigChange::removed(['phpstan-rules', 'ultimateFormatter.disallowSprintf']),
            ConfigChange::removed(['format', 'rector']),
        ]]))->migrate($this->configFile);

        $this->assertSame(['Removed format.rector.'], $migration->applied);
        $this->assertSame(['Skipped removing phpstan-rules.ultimateFormatter.disallowSprintf, phpstan-rules is not a literal [...] array.'], $migration->skipped);
        $this->assertStringContainsString("'ultimateFormatter.disallowSprintf' => false,", $this->filesystem->readFile($this->configFile));
        $this->assertStringContainsString("'format' => [\n    ],\n    'config-version' => 1,", $this->filesystem->readFile($this->configFile));
    }

    /**
     * @return void
     */
    public function test_adds_to_an_empty_array(): void
    {
        $this->filesystem->dumpFile($this->configFile, "<?php\n\nreturn [\n    'pre' => [],\n];\n");

        (new ConfigMigrator([1 => [ConfigChange::added(['pre', 'migrate'], false)]]))->migrate($this->configFile);

        $this->assertSame(
            "<?php\n\nreturn [\n    'pre' => [\n        'migrate' => false,\n    ],\n    'config-version' => 1,\n];\n",
            $this->filesystem->readFile($this->configFile),
        );
    }

    /**
     * @return void
     */
    public function test_dry_run_leaves_the_file_alone(): void
    {
        $migration = (new ConfigMigrator([1 => [ConfigChange::removed(['format', 'rector'])]]))->migrate($this->configFile, dryRun: true);

        $this->assertSame(['Removed format.rector.'], $migration->applied);
        $this->assertSame(self::PUBLISHED_CONFIG, $this->filesystem->readFile($this->configFile));
    }

    /**
     * @return void
     */
    public function test_fails_on_a_config_that_does_not_return_an_array(): void
    {
        $this->filesystem->dumpFile($this->configFile, "<?php\n\nreturn require __DIR__ . '/shared.php';\n");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('it does not return a literal [...] array');

        (new ConfigMigrator([1 => []]))->migrate($this->configFile);
    }
}
