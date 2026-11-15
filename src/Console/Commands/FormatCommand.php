<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use AdamczykPiotr\UltimateFormatter\Config\FormatterConfig;
use AdamczykPiotr\UltimateFormatter\Console\ArtisanStep;
use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;
use AdamczykPiotr\UltimateFormatter\Console\RunMode;
use AdamczykPiotr\UltimateFormatter\Console\Tool;
use AdamczykPiotr\UltimateFormatter\Console\ToolRun;
use AdamczykPiotr\UltimateFormatter\Console\ToolRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'formatter:format', description: 'Fix code style: Rector, then PHP CS Fixer')]
class FormatCommand extends FormatterCommand
{
    /**
     * @param ConfigReader $configReader
     * @param ToolRunner $toolRunner
     */
    public function __construct(
        protected readonly ConfigReader $configReader = new ConfigReader(),
        protected readonly ToolRunner $toolRunner = new ToolRunner(),
    ) {
        parent::__construct();
    }

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
        $path = $this->projectPath($input);
        $config = $this->configReader->forProject($path);
        $models = $input->getOption('models') === true;

        $artisanSteps = collect(ArtisanStep::cases())
            ->filter(fn(ArtisanStep $step): bool => $step->hasPreFlag() ? $config->runsBeforeFormat($step) : $models);

        // A package has no artisan to run them with, so Rector and the fixer still run without them.
        if ($artisanSteps->isNotEmpty() && ProjectLayout::detect($path) !== ProjectLayout::APP) {
            $skippedSteps = $artisanSteps->map(fn(ArtisanStep $step): string => $step->configKey())->implode(', ');
            $output->writeln("<comment>Skipped {$skippedSteps}, the project has no artisan.</comment>");
            $artisanSteps = $artisanSteps->take(0);
        }

        $runs = $artisanSteps
            ->map(fn(ArtisanStep $step): ToolRun => new ToolRun(Tool::ARTISAN, $config->artisanArguments($step)))
            ->push($this->toolRun($config, Tool::RECTOR))
            ->push($this->toolRun($config, Tool::PHP_CS_FIXER))
            ->values();

        return $this->toolRunner->runInOrder($runs, $path, $output);
    }

    /**
     * @param FormatterConfig $config
     * @param Tool $tool
     *
     * @return ToolRun
     */
    protected function toolRun(FormatterConfig $config, Tool $tool): ToolRun
    {
        return new ToolRun($tool, $config->toolArguments(RunMode::FORMAT, $tool));
    }
}
