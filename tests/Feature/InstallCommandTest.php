<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Console\Commands\InstallCommand;
use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use PhpCsFixer\Config;
use Rector\Configuration\RectorConfigBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class InstallCommandTest extends TestCase
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
    public function test_installs_app_configs(): void
    {
        $this->filesystem->mkdir(["{$this->projectPath}/app", "{$this->projectPath}/database/migrations", "{$this->projectPath}/tests"]);
        $this->filesystem->touch("{$this->projectPath}/artisan");

        $exitCode = $this->runInstall();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString("__DIR__ . '/database/migrations'", $this->readProjectFile('rector.php'));
        $this->assertSame($this->filesystem->readFile(__DIR__ . '/../../config/ultimate-formatter.php'), $this->readProjectFile('config/ultimate-formatter.php'));
        $this->assertDirectoryDoesNotExist("{$this->projectPath}/.github");
        $this->assertFileDoesNotExist("{$this->projectPath}/.editorconfig");
        $this->assertFileExists("{$this->projectPath}/.git-blame-ignore-revs");
    }

    /**
     * @return void
     */
    public function test_installs_package_configs(): void
    {
        $this->filesystem->mkdir(["{$this->projectPath}/src", "{$this->projectPath}/tests"]);

        $exitCode = $this->runInstall();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString("__DIR__ . '/src'", $this->readProjectFile('.php-cs-fixer.php'));
        $this->assertStringNotContainsString('withSkip', $this->readProjectFile('rector.php'));
        $this->assertStringContainsString('build/.php-cs-fixer.cache', $this->readProjectFile('.php-cs-fixer.php'));
        $this->assertFileDoesNotExist("{$this->projectPath}/config/ultimate-formatter.php");
    }

    /**
     * @return void
     */
    public function test_package_paths_cover_config_database_and_workbench(): void
    {
        $this->filesystem->mkdir([
            "{$this->projectPath}/config",
            "{$this->projectPath}/database/migrations",
            "{$this->projectPath}/src",
            "{$this->projectPath}/workbench",
        ]);

        $this->runInstall();

        $fixerConfig = $this->readProjectFile('.php-cs-fixer.php');
        $rectorConfig = $this->readProjectFile('rector.php');
        foreach (['config', 'database', 'src', 'workbench'] as $directory) {
            $this->assertStringContainsString("__DIR__ . '/{$directory}',", $fixerConfig);
            $this->assertStringContainsString("__DIR__ . '/{$directory}',", $rectorConfig);
        }

        $this->assertStringNotContainsString("__DIR__ . '/tests'", $fixerConfig);
        $this->assertStringContainsString("->withSkip([\n        __DIR__ . '/database/migrations',", $rectorConfig);
    }

    /**
     * @return void
     */
    public function test_adds_missing_composer_scripts(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/composer.json", <<<'JSON'
            {
                "name": "vendor/package",
                "autoload-dev": {},
                "scripts": {
                    "check": "custom check"
                }
            }
            JSON);

        $this->runInstall();

        $this->assertSame(<<<'JSON'
            {
                "name": "vendor/package",
                "autoload-dev": {},
                "scripts": {
                    "check": "custom check",
                    "format": "vendor/bin/ultimate-formatter format"
                }
            }

            JSON, $this->readProjectFile('composer.json'));
    }

    /**
     * @return void
     */
    public function test_adds_composer_scripts_section(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/composer.json", '{"name": "vendor/package", "scripts": []}');

        $this->runInstall();

        $composer = json_decode($this->readProjectFile('composer.json'), true);
        $this->assertSame([
            'check' => 'vendor/bin/ultimate-formatter check',
            'format' => 'vendor/bin/ultimate-formatter format',
        ], data_get($composer, 'scripts'));
    }

    /**
     * @return void
     */
    public function test_keeps_composer_json_with_existing_scripts(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $composer = '{"scripts":{"check":"a","format":"b"}}';
        $this->filesystem->dumpFile("{$this->projectPath}/composer.json", $composer);

        $this->runInstall();

        $this->assertSame($composer, $this->readProjectFile('composer.json'));
    }

    /**
     * @return void
     */
    public function test_adds_export_ignores_in_a_package(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/.gitattributes", "# \"rector.php export-ignore\" in a comment\n/.github export-ignore\n/rector.php  export-ignore");

        $this->runInstall(['--ci' => true]);

        $this->assertSame(
            "# \"rector.php export-ignore\" in a comment\n/.github export-ignore\n/rector.php  export-ignore\n/.php-cs-fixer.php export-ignore\n/.git-blame-ignore-revs export-ignore\n",
            $this->readProjectFile('.gitattributes'),
        );
    }

    /**
     * @return void
     */
    public function test_creates_gitattributes_in_a_package(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");

        $this->runInstall();

        $this->assertSame(
            "/.php-cs-fixer.php export-ignore\n/rector.php export-ignore\n/.git-blame-ignore-revs export-ignore\n",
            $this->readProjectFile('.gitattributes'),
        );
    }

    /**
     * @return void
     */
    public function test_skips_export_ignores_in_an_app(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/app");
        $this->filesystem->touch("{$this->projectPath}/artisan");

        $this->runInstall();

        $this->assertFileDoesNotExist("{$this->projectPath}/.gitattributes");
    }

    /**
     * @return void
     */
    public function test_publishes_ci_workflow_on_the_minimum_php_version(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/composer.json", '{"require": {"php": "^8.5 || ^8.4"}}');

        $this->runInstall(['--ci' => true]);

        $workflow = $this->readProjectFile('.github/workflows/code-style.yml');
        $this->assertStringContainsString("php-version: '8.4'", $workflow);
        $this->assertStringContainsString('group: ${{ github.workflow }}-${{ github.ref }}', $workflow);
        $this->assertStringContainsString('run: composer check', $workflow);
    }

    /**
     * @return void
     */
    public function test_ci_workflow_never_runs_below_the_formatter_minimum(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/composer.json", '{"require": {"php": "^8.2"}}');

        $this->runInstall(['--ci' => true]);

        $this->assertStringContainsString("php-version: '8.3'", $this->readProjectFile('.github/workflows/code-style.yml'));
    }

    /**
     * @return void
     */
    public function test_generated_configs_load(): void
    {
        $this->filesystem->mkdir(["{$this->projectPath}/src", "{$this->projectPath}/tests"]);

        $this->runInstall();

        $this->assertInstanceOf(Config::class, require "{$this->projectPath}/.php-cs-fixer.php");
        $this->assertInstanceOf(RectorConfigBuilder::class, require "{$this->projectPath}/rector.php");
    }

    /**
     * @return void
     */
    public function test_keeps_existing_files_without_force(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/rector.php", 'custom');

        $this->runInstall();

        $this->assertSame('custom', $this->readProjectFile('rector.php'));
    }

    /**
     * @return void
     */
    public function test_overwrites_existing_files_with_force(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/rector.php", 'custom');

        $this->runInstall(['--force' => true]);

        $this->assertStringContainsString('RectorConfigFactory::create()', $this->readProjectFile('rector.php'));
    }

    /**
     * @return void
     */
    public function test_keeps_blame_ignore_revs_with_force(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/.git-blame-ignore-revs", 'recorded-sha');

        $this->runInstall(['--force' => true]);

        $this->assertSame('recorded-sha', $this->readProjectFile('.git-blame-ignore-revs'));
    }

    /**
     * @return void
     */
    public function test_fails_without_a_recognised_layout(): void
    {
        $exitCode = $this->runInstall();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertFileDoesNotExist("{$this->projectPath}/rector.php");
    }

    /**
     * @param array<string, bool> $options
     *
     * @return int
     */
    protected function runInstall(array $options = []): int
    {
        $tester = new CommandTester(new InstallCommand());

        return $tester->execute(['--path' => $this->projectPath, ...$options]);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function readProjectFile(string $file): string
    {
        return $this->filesystem->readFile("{$this->projectPath}/{$file}");
    }
}
