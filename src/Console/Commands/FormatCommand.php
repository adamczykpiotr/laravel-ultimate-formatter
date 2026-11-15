<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use AdamczykPiotr\UltimateFormatter\Console\Tool;
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
        $artisanSteps = [
            'format.clear-cache' => $this->configReader->flag($path, 'pre.clear-cache'),
            'format.migrate' => $this->configReader->flag($path, 'pre.migrate'),
            'format.ide-helper' => $input->getOption('models') === true,
        ];

        $enabledSteps = collect($artisanSteps)->filter()->keys();
        foreach ($enabledSteps as $step) {
            $artisanExitCode = $this->toolRunner->run(Tool::ARTISAN, $this->configReader->arguments($path, $step), $path, $output);
            if ($artisanExitCode !== self::SUCCESS) {
                return $artisanExitCode;
            }
        }

        $rectorExitCode = $this->toolRunner->run(Tool::RECTOR, $this->configReader->arguments($path, 'format.rector'), $path, $output);
        if ($rectorExitCode !== self::SUCCESS) {
            return $rectorExitCode;
        }

        return $this->toolRunner->run(Tool::PHP_CS_FIXER, $this->configReader->arguments($path, 'format.php-cs-fixer'), $path, $output);
    }
}
