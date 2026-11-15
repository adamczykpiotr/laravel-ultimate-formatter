<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ToolRunner
{
    protected const VERBOSITY_FLAGS = ['-q', '--quiet', '-v', '-vv', '-vvv', '--verbose'];
    protected const RECTOR_DEBUG_FLAG = '--debug';

    /**
     * Each tool works on what the one before it left, so the first failure stops the run and its exit code is returned.
     *
     * @param Collection<int, ToolRun> $runs
     * @param string $projectPath
     * @param OutputInterface $output
     *
     * @return int
     */
    public function runInOrder(Collection $runs, string $projectPath, OutputInterface $output): int
    {
        foreach ($runs as $run) {
            $exitCode = $this->run($run->tool, $run->arguments, $projectPath, $output);
            if ($exitCode !== Command::SUCCESS) {
                return $exitCode;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param Tool $tool
     * @param array<int, string> $arguments
     * @param string $projectPath
     * @param OutputInterface $output
     *
     * @return int
     */
    public function run(Tool $tool, array $arguments, string $projectPath, OutputInterface $output): int
    {
        $script = $tool->script();
        $arguments = $tool === Tool::RECTOR
            ? $this->withRectorVerbosity($arguments, $output)
            : $this->withVerbosity($arguments, $output);
        if ($output->isVerbose()) {
            $commandLine = collect([$script, ...$arguments])->implode(' ');
            $output->writeln("<comment>> php {$commandLine}</comment>");
        }

        $command = [PHP_BINARY, $script, ...$arguments];
        if ($output->isDecorated()) {
            $command[] = '--ansi';
        }

        // A crash message on stderr stays on the terminal when stdout is redirected to a report.
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $process = new Process($command, $projectPath, timeout: null);

        return $process->run(fn(string $type, string $buffer) => ($type === Process::ERR ? $errorOutput : $output)->write($buffer));
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

        $configuredArguments = $this->withoutVerbosityFlags($arguments);

        return [...$configuredArguments, $flag];
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
        $configuredArguments = $this->withoutVerbosityFlags($arguments);
        if ($output->isDebug() === false || in_array(self::RECTOR_DEBUG_FLAG, $configuredArguments, true)) {
            return $configuredArguments;
        }

        return [...$configuredArguments, self::RECTOR_DEBUG_FLAG];
    }

    /**
     * @param array<int, string> $arguments
     *
     * @return array<int, string>
     */
    protected function withoutVerbosityFlags(array $arguments): array
    {
        return collect($arguments)
            ->reject(fn(string $argument): bool => in_array($argument, self::VERBOSITY_FLAGS, true))
            ->values()
            ->all();
    }
}
