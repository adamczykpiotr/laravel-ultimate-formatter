<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter;

use AdamczykPiotr\UltimateFormatter\Console\Commands\MigrateConfigCommand;
use AdamczykPiotr\UltimateFormatter\Console\FormatterApplication;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;

class UltimateFormatterServiceProvider extends PackageServiceProvider
{
    // Composer runs it after every install and update through the post-autoload-dump script every Laravel app has.
    protected const PACKAGE_DISCOVER_COMMAND = 'package:discover';

    /**
     * @param Package $package
     *
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ultimate-formatter')
            ->hasConfigFile('ultimate-formatter')
            ->hasCommands(FormatterApplication::COMMANDS);
    }

    /**
     * Migrates the published config after Composer updates the package, without a script in the app's composer.json.
     *
     * @return void
     */
    public function packageBooted(): void
    {
        if ($this->app->runningInConsole() === false) {
            return;
        }

        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            if ($event->command !== self::PACKAGE_DISCOVER_COMMAND || $event->exitCode !== Command::SUCCESS) {
                return;
            }

            (new MigrateConfigCommand())->run(new ArrayInput(['--path' => $this->app->basePath()]), $event->output);
        });
    }
}
