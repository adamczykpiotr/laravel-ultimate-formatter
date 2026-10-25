<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

abstract class FormatterCommand extends Command
{
    protected const DEFAULT_CONFIG_FILE = __DIR__ . '/../../../config/ultimate-formatter.php';
    protected const PUBLISHED_CONFIG_FILE = 'config/ultimate-formatter.php';

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
     * @param string $script
     * @param array<int, string> $arguments
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function runPhpScript(string $script, array $arguments, InputInterface $input, OutputInterface $output): int
    {
        $command = [PHP_BINARY, $script, ...$arguments];
        if ($output->isDecorated()) {
            $command[] = '--ansi';
        }

        $process = new Process($command, $this->projectPath($input), timeout: null);

        return $process->run(fn(string $type, string $buffer) => $output->write($buffer));
    }
}
