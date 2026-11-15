<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use AdamczykPiotr\UltimateFormatter\Composer\ComposerFile;
use AdamczykPiotr\UltimateFormatter\Console\Tool;
use AdamczykPiotr\UltimateFormatter\Exceptions\PhpStanConfigNotLoadableException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PhpStanSetup
{
    public const RULES_FILE = 'vendor/adamczykpiotr/laravel-ultimate-formatter/phpstan-rules.neon';

    // PHPStan's own discovery order, dot-prefixed names included.
    protected const CONFIG_FILES = ['.phpstan.neon', 'phpstan.neon', '.phpstan.neon.dist', 'phpstan.neon.dist', '.phpstan.dist.neon', 'phpstan.dist.neon'];
    protected const EXTENSION_INSTALLER_PACKAGE = 'phpstan/extension-installer';
    protected const EXTENSION_FILES = [
        'vendor/larastan/larastan/extension.neon',
        'vendor/spaze/phpstan-disallowed-calls/extension.neon',
        'vendor/phpat/phpat/extension.neon',
    ];

    // phpstan-rules.neon declares this parameter, so PHPStan only knows it when the ruleset is loaded.
    protected const RULESET_PARAMETER = 'ultimateFormatterConfigFile';

    /**
     * @param ComposerFile $composerFile
     * @param Filesystem $filesystem
     */
    public function __construct(
        protected readonly ComposerFile $composerFile = new ComposerFile(),
        protected readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * PHPStan reads the first of these that exists, so this returns the one it would use.
     *
     * @param string $projectPath
     *
     * @return string|null
     */
    public function configFile(string $projectPath): string|null
    {
        return collect(self::CONFIG_FILES)->first(fn(string $file): bool => $this->filesystem->exists("{$projectPath}/{$file}"));
    }

    /**
     * extension-installer already loads the extensions the ruleset configures, and PHPStan fails when one is included twice.
     *
     * @param string $projectPath
     *
     * @return list<string>
     */
    public function includes(string $projectPath): array
    {
        $usesExtensionInstaller = $this->composerFile->requires("{$projectPath}/composer.json", self::EXTENSION_INSTALLER_PACKAGE);
        $extensionFiles = $usesExtensionInstaller ? [] : self::EXTENSION_FILES;

        return [...$extensionFiles, self::RULES_FILE];
    }

    /**
     * Asks PHPStan for its resolved parameters, so a ruleset included through another neon file counts too.
     *
     * @param string $projectPath
     *
     * @return bool
     *
     * @throws PhpStanConfigNotLoadableException
     */
    public function loadsRuleset(string $projectPath): bool
    {
        // Symfony Console exports -q as SHELL_VERBOSITY, which would leave PHPStan's parameters unprinted.
        $process = new Process(
            [PHP_BINARY, Tool::PHPSTAN->script(), 'dump-parameters', '--json'],
            $projectPath,
            ['SHELL_VERBOSITY' => false],
            timeout: null,
        );
        $process->run();
        if ($process->isSuccessful() === false) {
            $processOutput = trim("{$process->getErrorOutput()}{$process->getOutput()}");

            throw new PhpStanConfigNotLoadableException($processOutput);
        }

        $parameters = json_decode($process->getOutput(), true);

        return is_array($parameters) && collect($parameters)->has(self::RULESET_PARAMETER);
    }
}
