<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'formatter:analyse', description: 'Run PHPStan with the project config')]
class AnalyseCommand extends FormatterCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runPhpScript('vendor/bin/phpstan', $this->configuredArguments($input, 'analyse.phpstan'), $input, $output);
    }
}
