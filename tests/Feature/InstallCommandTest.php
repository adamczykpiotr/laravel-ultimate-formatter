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
        $this->assertStringContainsString('- app', $this->readProjectFile('phpstan.neon'));
        $this->assertStringContainsString('vendor/larastan/larastan/extension.neon', $this->readProjectFile('phpstan.neon'));
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
        $this->assertStringContainsString('- src', $this->readProjectFile('phpstan.neon.dist'));
        $this->assertStringNotContainsString('withSkip', $this->readProjectFile('rector.php'));
        $this->assertStringContainsString('build/.php-cs-fixer.cache', $this->readProjectFile('.php-cs-fixer.php'));
        $this->assertFileDoesNotExist("{$this->projectPath}/config/ultimate-formatter.php");
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
    public function test_omits_larastan_include_with_extension_installer(): void
    {
        $this->filesystem->mkdir("{$this->projectPath}/src");
        $this->filesystem->dumpFile("{$this->projectPath}/composer.json", '{"require-dev": {"phpstan/extension-installer": "^1.4"}}');

        $this->runInstall();

        $this->assertStringNotContainsString('larastan', $this->readProjectFile('phpstan.neon.dist'));
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
    public function test_fails_without_a_recognised_layout(): void
    {
        $exitCode = $this->runInstall();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertFileDoesNotExist("{$this->projectPath}/rector.php");
    }

    /**
     * @param array<string, bool> $options
     * @return int
     */
    protected function runInstall(array $options = []): int
    {
        $tester = new CommandTester(new InstallCommand());

        return $tester->execute(['--path' => $this->projectPath, ...$options]);
    }

    /**
     * @param string $file
     * @return string
     */
    protected function readProjectFile(string $file): string
    {
        return $this->filesystem->readFile("{$this->projectPath}/{$file}");
    }
}
