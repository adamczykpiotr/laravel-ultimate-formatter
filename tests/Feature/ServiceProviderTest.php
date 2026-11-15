<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

class ServiceProviderTest extends TestCase
{
    protected const CONFIG_1_1_0 = __DIR__ . '/Fixture/ultimate-formatter-1.1.0.php.inc';

    /**
     * @return void
     */
    public function test_registers_package_config(): void
    {
        $this->assertIsArray(config('ultimate-formatter'));
    }

    /**
     * @return void
     */
    public function test_registers_artisan_commands(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('formatter:install', $commands);
        $this->assertArrayHasKey('formatter:format', $commands);
        $this->assertArrayHasKey('formatter:check', $commands);
        $this->assertArrayHasKey('formatter:migrate-config', $commands);
    }

    /**
     * @return void
     */
    public function test_migrates_the_published_config_after_package_discovery(): void
    {
        $configFile = $this->publishUnversionedConfig();

        $output = new BufferedOutput();
        event(new CommandFinished('package:discover', new ArrayInput([]), $output, 0));

        $this->assertStringContainsString("'config-version' =>", (new Filesystem())->readFile($configFile));
        $this->assertStringContainsString('Migrated config/ultimate-formatter.php from version 1.1.0', $output->fetch());
    }

    /**
     * @return void
     */
    public function test_ignores_other_commands_and_a_failed_discovery(): void
    {
        $configFile = $this->publishUnversionedConfig();
        $published = (new Filesystem())->readFile($configFile);

        event(new CommandFinished('migrate', new ArrayInput([]), new BufferedOutput(), 0));
        event(new CommandFinished('package:discover', new ArrayInput([]), new BufferedOutput(), 1));

        $this->assertSame($published, (new Filesystem())->readFile($configFile));
    }

    /**
     * A config published by 1.1.0, before config-version existed.
     *
     * @return string
     */
    protected function publishUnversionedConfig(): string
    {
        $filesystem = new Filesystem();
        $basePath = $filesystem->tempnam(sys_get_temp_dir(), 'ultimate-formatter-');
        $filesystem->remove($basePath);
        app()->setBasePath($basePath);
        $this->beforeApplicationDestroyed(fn() => $filesystem->remove($basePath));

        $configFile = "{$basePath}/config/ultimate-formatter.php";
        $filesystem->copy(self::CONFIG_1_1_0, $configFile);

        return $configFile;
    }
}
