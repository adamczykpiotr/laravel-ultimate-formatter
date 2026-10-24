<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Console\Commands\FormatterCommand;
use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ConfiguredArgumentsTest extends TestCase
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
    public function test_uses_package_defaults_without_a_published_config(): void
    {
        $this->assertSame('analyse --memory-limit=2G', $this->resolveArguments('analyse.phpstan'));
    }

    /**
     * @return void
     */
    public function test_published_config_overrides_a_tool(): void
    {
        $this->publishConfig("['analyse' => ['phpstan' => ['analyse', '--memory-limit=4G']]]");

        $this->assertSame('analyse --memory-limit=4G', $this->resolveArguments('analyse.phpstan'));
    }

    /**
     * @return void
     */
    public function test_tools_missing_from_published_config_keep_defaults(): void
    {
        $this->publishConfig("['analyse' => ['phpstan' => ['analyse']]]");

        $this->assertSame('fix --quiet', $this->resolveArguments('format.php-cs-fixer'));
    }

    /**
     * @param string $config
     * @return void
     */
    protected function publishConfig(string $config): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/config/ultimate-formatter.php", "<?php\n\nreturn {$config};\n");
    }

    /**
     * @param string $key
     * @return string
     */
    protected function resolveArguments(string $key): string
    {
        $command = new class ($key) extends FormatterCommand {
            /**
             * @param string $key
             */
            public function __construct(protected string $key)
            {
                parent::__construct('formatter:probe');
            }

            /**
             * @param InputInterface $input
             * @param OutputInterface $output
             * @return int
             */
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $output->write(implode(' ', $this->configuredArguments($input, $this->key)));

                return self::SUCCESS;
            }
        };

        $tester = new CommandTester($command);
        $tester->execute(['--path' => $this->projectPath]);

        return $tester->getDisplay();
    }
}
