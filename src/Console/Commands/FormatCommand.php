<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'formatter:format', description: 'Fix code style: Rector, then PHP CS Fixer')]
class FormatCommand extends FormatterCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('models', null, InputOption::VALUE_NONE, 'Regenerate model docblocks with laravel-ide-helper first');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $artisanSteps = [
            'format.clear-cache' => $this->configuredFlag($input, 'pre.clear-cache'),
            'format.migrate' => $this->configuredFlag($input, 'pre.migrate'),
            'format.ide-helper' => $input->getOption('models') === true,
        ];

        $enabledSteps = collect($artisanSteps)->filter()->keys();
        foreach ($enabledSteps as $step) {
            $artisanExitCode = $this->runPhpScript('artisan', $this->configuredArguments($input, $step), $input, $output);
            if ($artisanExitCode !== self::SUCCESS) {
                return $artisanExitCode;
            }
        }

        $rectorExitCode = $this->runPhpScript('vendor/bin/rector', $this->configuredArguments($input, 'format.rector'), $input, $output);
        if ($rectorExitCode !== self::SUCCESS) {
            return $rectorExitCode;
        }

        return $this->runPhpScript('vendor/bin/php-cs-fixer', $this->configuredArguments($input, 'format.php-cs-fixer'), $input, $output);
    }
}
