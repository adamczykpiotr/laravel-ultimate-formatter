<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'formatter:check', description: 'Dry-run Rector and PHP CS Fixer, failing on any pending change')]
class CheckCommand extends FormatterCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Both tools always run, so one CI pass reports every pending change.
        $rectorExitCode = $this->runPhpScript('vendor/bin/rector', ['process', '--dry-run', '--no-progress-bar'], $input, $output);
        $fixerExitCode = $this->runPhpScript('vendor/bin/php-cs-fixer', ['fix', '--dry-run', '--diff'], $input, $output);

        if ($rectorExitCode !== self::SUCCESS || $fixerExitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
