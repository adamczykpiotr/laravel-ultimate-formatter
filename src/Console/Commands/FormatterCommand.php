<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use RuntimeException;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

abstract class FormatterCommand extends Command
{
    protected const DEFAULT_CONFIG_FILE = __DIR__ . '/../../../config/ultimate-formatter.php';
    protected const PUBLISHED_CONFIG_FILE = 'config/ultimate-formatter.php';
    protected const PHPSTAN_CONFIG_FILES = ['phpstan.neon', 'phpstan.neon.dist', 'phpstan.dist.neon'];
    protected const VERBOSITY_FLAGS = ['-q', '--quiet', '-v', '-vv', '-vvv', '--verbose'];
    protected const RECTOR_SCRIPT = 'vendor/bin/rector';
    protected const RECTOR_DEBUG_FLAG = '--debug';
    protected const PHPSTAN_RULES_FILE = 'vendor/adamczykpiotr/laravel-ultimate-formatter/phpstan-rules.neon';
    protected const PHPSTAN_EXTENSION_FILES = [
        'vendor/larastan/larastan/extension.neon',
        'vendor/spaze/phpstan-disallowed-calls/extension.neon',
        'vendor/phpat/phpat/extension.neon',
    ];

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'Project root, defaults to the working directory');
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function projectPath(InputInterface $input): string
    {
        $path = $input->getOption('path');
        if (is_string($path)) {
            return $path;
        }

        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            throw new RuntimeException('Cannot determine the working directory, pass --path.');
        }

        return $workingDirectory;
    }

    /**
     * @param InputInterface $input
     * @param string $key
     *
     * @return array<int, string>
     */
    protected function configuredArguments(InputInterface $input, string $key): array
    {
        $arguments = $this->configuredValue($input, $key);
        if (is_array($arguments) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.{$key} must be a list of arguments.");
        }

        return collect($arguments)
            ->map(fn(mixed $argument): string => is_string($argument)
                ? $argument
                : throw new UnexpectedValueException("Config ultimate-formatter.{$key} must contain only strings."))
            ->values()
            ->all();
    }

    /**
     * @param InputInterface $input
     * @param string $key
     *
     * @return bool
     */
    protected function configuredFlag(InputInterface $input, string $key): bool
    {
        $flag = $this->configuredValue($input, $key);
        if (is_bool($flag) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.{$key} must be a boolean.");
        }

        return $flag;
    }

    /**
     * @param InputInterface $input
     * @param string $key
     *
     * @return mixed
     */
    protected function configuredValue(InputInterface $input, string $key): mixed
    {
        // The binary runs without booting Laravel, so the config file is read directly.
        $publishedConfigFile = "{$this->projectPath($input)}/" . self::PUBLISHED_CONFIG_FILE;
        $publishedValue = is_file($publishedConfigFile) ? data_get(require $publishedConfigFile, $key) : null;

        return $publishedValue ?? data_get(require self::DEFAULT_CONFIG_FILE, $key);
    }

    /**
     * PHPStan reads the first of these that exists, so this returns the one it would use.
     *
     * @param string $path
     *
     * @return string|null
     */
    protected function phpstanConfigFile(string $path): string|null
    {
        return collect(self::PHPSTAN_CONFIG_FILES)->first(fn(string $file): bool => is_file("{$path}/{$file}"));
    }

    /**
     * extension-installer already loads the extensions the ruleset configures, and PHPStan fails when one is included twice.
     *
     * @param string $path
     *
     * @return list<string>
     */
    protected function phpstanIncludes(string $path): array
    {
        $composer = $this->readComposerFile("{$path}/composer.json");
        $usesExtensionInstaller = data_get($composer, 'require.phpstan/extension-installer') !== null
            || data_get($composer, 'require-dev.phpstan/extension-installer') !== null;
        $extensionFiles = $usesExtensionInstaller ? [] : self::PHPSTAN_EXTENSION_FILES;

        return [...$extensionFiles, self::PHPSTAN_RULES_FILE];
    }

    /**
     * @param string $composerFile
     *
     * @return stdClass|null
     */
    protected function readComposerFile(string $composerFile): stdClass|null
    {
        if (is_file($composerFile) === false) {
            return null;
        }

        // Objects keep empty {} sections intact when the file is written back.
        $composer = json_decode((new Filesystem())->readFile($composerFile), flags: JSON_THROW_ON_ERROR);
        if ($composer instanceof stdClass === false) {
            throw new UnexpectedValueException("{$composerFile} must contain a JSON object.");
        }

        return $composer;
    }

    /**
     * @param string $script
     * @param array<int, string> $arguments
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function runPhpScript(string $script, array $arguments, InputInterface $input, OutputInterface $output): int
    {
        $arguments = $script === self::RECTOR_SCRIPT
            ? $this->withRectorVerbosity($arguments, $output)
            : $this->withVerbosity($arguments, $output);
        if ($output->isVerbose()) {
            $output->writeln("<comment>> php {$script} " . implode(' ', $arguments) . '</comment>');
        }

        $command = [PHP_BINARY, $script, ...$arguments];
        if ($output->isDecorated()) {
            $command[] = '--ansi';
        }

        $process = new Process($command, $this->projectPath($input), timeout: null);

        return $process->run(fn(string $type, string $buffer) => $output->write($buffer));
    }

    /**
     * -q and -v override the verbosity flags in the configured arguments, so each tool prints as much as the formatter does.
     *
     * @param array<int, string> $arguments
     * @param OutputInterface $output
     *
     * @return array<int, string>
     */
    protected function withVerbosity(array $arguments, OutputInterface $output): array
    {
        $flag = match (true) {
            $output->isQuiet(), $output->isSilent() => '--quiet',
            $output->isDebug() => '-vvv',
            $output->isVeryVerbose() => '-vv',
            $output->isVerbose() => '-v',
            default => null,
        };
        if ($flag === null) {
            return $arguments;
        }

        $configuredArguments = collect($arguments)->reject(fn(string $argument): bool => in_array($argument, self::VERBOSITY_FLAGS, true));

        return [...$configuredArguments->values()->all(), $flag];
    }

    /**
     * Rector has no -q or -v: it treats -v as --version and exits without processing, and rejects --quiet and --verbose.
     * So -vvv maps to its --debug, and -q relies on the formatter writing nothing.
     *
     * @param array<int, string> $arguments
     * @param OutputInterface $output
     *
     * @return array<int, string>
     */
    protected function withRectorVerbosity(array $arguments, OutputInterface $output): array
    {
        $configuredArguments = collect($arguments)
            ->reject(fn(string $argument): bool => in_array($argument, self::VERBOSITY_FLAGS, true))
            ->values()
            ->all();
        if ($output->isDebug() === false || in_array(self::RECTOR_DEBUG_FLAG, $configuredArguments, true)) {
            return $configuredArguments;
        }

        return [...$configuredArguments, self::RECTOR_DEBUG_FLAG];
    }
}
