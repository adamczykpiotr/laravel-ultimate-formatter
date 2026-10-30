<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'formatter:check', description: 'Dry-run Rector and PHP CS Fixer, then run PHPStan, failing on any pending change or error')]
class CheckCommand extends FormatterCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // The fixer would check code Rector still has to change, so stop at the first failure like `format` does.
        $rectorExitCode = $this->runPhpScript('vendor/bin/rector', $this->configuredArguments($input, 'check.rector'), $input, $output);
        if ($rectorExitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        $fixerExitCode = $this->runPhpScript('vendor/bin/php-cs-fixer', $this->configuredArguments($input, 'check.php-cs-fixer'), $input, $output);
        if ($fixerExitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        // Without a config PHPStan has no paths to analyse, so a project that never published one keeps the formatting check.
        if ($this->phpstanConfigFile($this->projectPath($input)) === null) {
            $output->writeln('Skipped PHPStan, the project has no phpstan.neon.');

            return self::SUCCESS;
        }

        $phpstanExitCode = $this->runPhpScript('vendor/bin/phpstan', $this->configuredArguments($input, 'check.phpstan'), $input, $output);
        if ($phpstanExitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
