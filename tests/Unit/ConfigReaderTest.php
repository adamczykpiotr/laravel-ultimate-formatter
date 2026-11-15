<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

class ConfigReaderTest extends TestCase
{
    protected string $projectPath;
    protected Filesystem $filesystem;
    protected ConfigReader $configReader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->configReader = new ConfigReader();
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
        $this->assertSame(['process', '--dry-run', '--no-progress-bar'], $this->configReader->arguments($this->projectPath, 'check', 'rector'));
    }

    /**
     * @return void
     */
    public function test_published_config_overrides_a_tool(): void
    {
        $this->publishConfig("['check' => ['rector' => ['process', '--dry-run']]]");

        $this->assertSame(['process', '--dry-run'], $this->configReader->arguments($this->projectPath, 'check', 'rector'));
    }

    /**
     * @return void
     */
    public function test_tools_missing_from_published_config_keep_defaults(): void
    {
        $this->publishConfig("['check' => ['rector' => ['process']]]");

        $this->assertSame(['fix', '--show-progress=none'], $this->configReader->arguments($this->projectPath, 'format', 'php-cs-fixer'));
    }

    /**
     * @return void
     */
    public function test_rejects_arguments_that_are_not_strings(): void
    {
        $this->publishConfig("['check' => ['rector' => ['process', 1]]]");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Config ultimate-formatter.check.rector must contain only strings.');

        $this->configReader->arguments($this->projectPath, 'check', 'rector');
    }

    /**
     * @return void
     */
    public function test_rejects_a_flag_that_is_not_a_boolean(): void
    {
        $this->publishConfig("['pre' => ['migrate' => 'yes']]");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Config ultimate-formatter.pre.migrate must be a boolean.');

        $this->configReader->flag($this->projectPath, 'pre', 'migrate');
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
}
