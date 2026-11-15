<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

use AdamczykPiotr\UltimateFormatter\Composer\ComposerFile;
use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;
use stdClass;

class ComposerScripts
{
    protected const SCRIPTS = [
        'check' => 'vendor/bin/ultimate-formatter check',
        'format' => 'vendor/bin/ultimate-formatter format',
    ];
    protected const POST_UPDATE_HOOK = 'post-update-cmd';
    protected const MIGRATE_CONFIG_SCRIPT = 'vendor/bin/ultimate-formatter migrate-config';

    /**
     * @param ComposerFile $composerFile
     */
    public function __construct(
        protected readonly ComposerFile $composerFile = new ComposerFile(),
    ) {}

    /**
     * Returns null when the project has no composer.json.
     *
     * @param string $path
     * @param ProjectLayout $layout
     *
     * @return ComposerScriptsChange|null
     */
    public function install(string $path, ProjectLayout $layout): ComposerScriptsChange|null
    {
        $composerFile = "{$path}/composer.json";
        $composer = $this->composerFile->read($composerFile);
        if ($composer === null) {
            return null;
        }

        $scripts = $composer->scripts ?? null;
        // An empty "scripts": [] decodes to an array, so it is replaced with an object.
        $scripts = $scripts instanceof stdClass ? $scripts : new stdClass();
        $missingScripts = collect(self::SCRIPTS)->reject(fn(string $command, string $name): bool => property_exists($scripts, $name));
        foreach ($missingScripts as $name => $command) {
            $scripts->{$name} = $command;
        }

        // Only an app publishes config/ultimate-formatter.php, so only an app migrates it after each upgrade.
        $addedMigrateConfig = $layout === ProjectLayout::APP && $this->addMigrateConfigScript($scripts);
        $change = new ComposerScriptsChange($missingScripts->keys()->all(), $addedMigrateConfig);
        if ($change->isEmpty()) {
            return $change;
        }

        $composer->scripts = $scripts;
        $this->composerFile->write($composerFile, $composer);

        return $change;
    }

    /**
     * @param stdClass $scripts
     *
     * @return bool
     */
    protected function addMigrateConfigScript(stdClass $scripts): bool
    {
        $hook = $scripts->{self::POST_UPDATE_HOOK} ?? [];
        // Composer takes a single command as a string, or a list of them.
        $commands = is_array($hook) ? $hook : [$hook];
        if (in_array(self::MIGRATE_CONFIG_SCRIPT, $commands, true)) {
            return false;
        }

        $scripts->{self::POST_UPDATE_HOOK} = [...$commands, self::MIGRATE_CONFIG_SCRIPT];

        return true;
    }
}
