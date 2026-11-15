<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Console\Commands\CheckCommand;
use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class CheckCommandTest extends TestCase
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

        foreach (['rector', 'php-cs-fixer', 'phpstan'] as $tool) {
            $this->filesystem->dumpFile("{$this->projectPath}/vendor/bin/{$tool}", <<<'PHP'
                <?php
                $tool = basename(__FILE__);
                if (($argv[1] ?? null) === 'dump-parameters') {
                    // Like PHPStan, print nothing under the quiet verbosity Symfony Console exports.
                    if (getenv('SHELL_VERBOSITY') === '-1') {
                        exit(0);
                    }

                    $parameters = @file_get_contents(dirname(__DIR__, 2) . '/phpstan-parameters.json');
                    if ($parameters === 'invalid') {
                        fwrite(STDERR, 'Invalid configuration: disallowedFunctionCalls is unexpected.');
                        exit(1);
                    }

                    echo $parameters === false ? '{"ultimateFormatterConfigFile": "config/ultimate-formatter.php"}' : $parameters;
                    exit(0);
                }
                file_put_contents(dirname(__DIR__, 2) . '/tools.log', "{$tool}\n", FILE_APPEND);
                exit($tool === @file_get_contents(dirname(__DIR__, 2) . '/fail-on.txt') ? 1 : 0);
                PHP);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        putenv('SHELL_VERBOSITY');
        unset($_ENV['SHELL_VERBOSITY'], $_SERVER['SHELL_VERBOSITY']);
        $this->filesystem->remove($this->projectPath);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_runs_phpstan_after_the_formatters(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon.dist");

        $exitCode = $this->runCheck();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame("rector\nphp-cs-fixer\nphpstan\n", $this->filesystem->readFile("{$this->projectPath}/tools.log"));
    }

    /**
     * @return void
     */
    public function test_skips_phpstan_without_a_config(): void
    {
        $exitCode = $this->runCheck();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame("rector\nphp-cs-fixer\n", $this->filesystem->readFile("{$this->projectPath}/tools.log"));
    }

    /**
     * @return void
     */
    public function test_fails_on_phpstan_errors(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon");
        $this->filesystem->dumpFile("{$this->projectPath}/fail-on.txt", 'phpstan');

        $this->assertSame(Command::FAILURE, $this->runCheck());
    }

    /**
     * @return void
     */
    public function test_skips_phpstan_when_the_formatters_fail(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon");
        $this->filesystem->dumpFile("{$this->projectPath}/fail-on.txt", 'php-cs-fixer');

        $exitCode = $this->runCheck();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame("rector\nphp-cs-fixer\n", $this->filesystem->readFile("{$this->projectPath}/tools.log"));
    }

    /**
     * @return void
     */
    public function test_fails_before_the_formatters_when_phpstan_does_not_load_the_ruleset(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon");
        $this->filesystem->dumpFile("{$this->projectPath}/phpstan-parameters.json", '{"level": 10}');

        $tester = $this->checkTester();

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertFileDoesNotExist("{$this->projectPath}/tools.log");
        $this->assertStringContainsString("phpstan.neon doesn't load the formatter's PHPStan ruleset", $tester->getDisplay());
        $this->assertStringContainsString('    - vendor/phpat/phpat/extension.neon', $tester->getDisplay());
        $this->assertStringContainsString('    - vendor/adamczykpiotr/laravel-ultimate-formatter/phpstan-rules.neon', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function test_runs_phpstan_without_the_ruleset_when_opted_out(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon");
        $this->filesystem->dumpFile("{$this->projectPath}/phpstan-parameters.json", '{"level": 10}');

        $tester = new CommandTester(new CheckCommand());
        $exitCode = $tester->execute(['--path' => $this->projectPath, '--without-phpstan-ruleset' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame("rector\nphp-cs-fixer\nphpstan\n", $this->filesystem->readFile("{$this->projectPath}/tools.log"));
    }

    /**
     * @return void
     */
    public function test_leaves_the_extensions_out_of_the_hint_with_the_extension_installer(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon");
        $this->filesystem->dumpFile("{$this->projectPath}/phpstan-parameters.json", '{"level": 10}');
        $this->filesystem->dumpFile("{$this->projectPath}/composer.json", '{"require-dev": {"phpstan/extension-installer": "^1.4"}}');

        $display = $this->checkTester()->getDisplay();

        $this->assertStringNotContainsString('extension.neon', $display);
        $this->assertStringContainsString('    - vendor/adamczykpiotr/laravel-ultimate-formatter/phpstan-rules.neon', $display);
    }

    /**
     * @return void
     */
    public function test_fails_when_phpstan_cannot_load_its_config(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon");
        $this->filesystem->dumpFile("{$this->projectPath}/phpstan-parameters.json", 'invalid');

        $tester = $this->checkTester();

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertFileDoesNotExist("{$this->projectPath}/tools.log");
        $this->assertStringContainsString('PHPStan could not load phpstan.neon', $tester->getDisplay());
        $this->assertStringContainsString('disallowedFunctionCalls is unexpected', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function test_finds_the_ruleset_when_run_quietly(): void
    {
        $this->filesystem->touch("{$this->projectPath}/phpstan.neon");
        // Symfony Console exports -q this way, and the PHPStan process inherits it.
        putenv('SHELL_VERBOSITY=-1');
        $_ENV['SHELL_VERBOSITY'] = -1;
        $_SERVER['SHELL_VERBOSITY'] = -1;

        $this->assertSame(Command::SUCCESS, $this->runCheck());
        $this->assertSame("rector\nphp-cs-fixer\nphpstan\n", $this->filesystem->readFile("{$this->projectPath}/tools.log"));
    }

    /**
     * @return int
     */
    protected function runCheck(): int
    {
        return $this->checkTester()->getStatusCode();
    }

    /**
     * @return CommandTester
     */
    protected function checkTester(): CommandTester
    {
        $tester = new CommandTester(new CheckCommand());
        $tester->execute(['--path' => $this->projectPath]);

        return $tester;
    }
}
