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
     * @return int
     */
    protected function runCheck(): int
    {
        $tester = new CommandTester(new CheckCommand());

        return $tester->execute(['--path' => $this->projectPath]);
    }
}
