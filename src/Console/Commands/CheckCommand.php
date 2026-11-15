<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use AdamczykPiotr\UltimateFormatter\Console\RunMode;
use AdamczykPiotr\UltimateFormatter\Console\Tool;
use AdamczykPiotr\UltimateFormatter\Console\ToolRun;
use AdamczykPiotr\UltimateFormatter\Console\ToolRunner;
use AdamczykPiotr\UltimateFormatter\Exceptions\PhpStanConfigNotLoadableException;
use AdamczykPiotr\UltimateFormatter\PhpStan\PhpStanSetup;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'formatter:check', description: 'Dry-run Rector and PHP CS Fixer, then run PHPStan, failing on any pending change or error')]
class CheckCommand extends FormatterCommand
{
    protected const WITHOUT_RULESET_OPTION = 'without-phpstan-ruleset';

    /**
     * @param ConfigReader $configReader
     * @param ToolRunner $toolRunner
     * @param PhpStanSetup $phpStanSetup
     */
    public function __construct(
        protected readonly ConfigReader $configReader = new ConfigReader(),
        protected readonly ToolRunner $toolRunner = new ToolRunner(),
        protected readonly PhpStanSetup $phpStanSetup = new PhpStanSetup(),
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(self::WITHOUT_RULESET_OPTION, null, InputOption::VALUE_NONE, "Run PHPStan even when its config doesn't load the formatter's ruleset");
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

        // Without the ruleset PHPStan passes code the ruleset bans, so check it before spending time on the formatters.
        $phpstanConfigFile = $this->phpStanSetup->configFile($path);
        $requiresRuleset = $phpstanConfigFile !== null && $input->getOption(self::WITHOUT_RULESET_OPTION) === false;
        if ($requiresRuleset && $this->loadsRuleset($path, $phpstanConfigFile, $output) === false) {
            return self::FAILURE;
        }

        // The fixer would check code Rector still has to change, so the tools run in order and stop at the first failure.
        // Without a config PHPStan has no paths to analyse, so a project that never published one keeps the formatting check.
        $config = $this->configReader->forProject($path);
        $tools = collect([Tool::RECTOR, Tool::PHP_CS_FIXER, Tool::PHPSTAN])
            ->reject(fn(Tool $tool): bool => $tool === Tool::PHPSTAN && $phpstanConfigFile === null)
            ->map(fn(Tool $tool): ToolRun => new ToolRun($tool, $config->toolArguments(RunMode::CHECK, $tool)))
            ->values();
        if ($this->toolRunner->runInOrder($tools, $path, $output) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($phpstanConfigFile === null) {
            $output->writeln('Skipped PHPStan, the project has no phpstan.neon.');
        }

        return self::SUCCESS;
    }

    /**
     * @param string $path
     * @param string $phpstanConfigFile
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function loadsRuleset(string $path, string $phpstanConfigFile, OutputInterface $output): bool
    {
        try {
            $loadsRuleset = $this->phpStanSetup->loadsRuleset($path);
        } catch (PhpStanConfigNotLoadableException $exception) {
            $output->writeln("<error>PHPStan could not load {$phpstanConfigFile}:</error>");
            $output->writeln($exception->getMessage());
            $this->writeRulesetIncludes($path, $phpstanConfigFile, $output);

            return false;
        }

        if ($loadsRuleset) {
            return true;
        }

        $output->writeln("<error>{$phpstanConfigFile} doesn't load the formatter's PHPStan ruleset, so its rules never run.</error>");
        $this->writeRulesetIncludes($path, $phpstanConfigFile, $output);

        return false;
    }

    /**
     * @param string $path
     * @param string $phpstanConfigFile
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function writeRulesetIncludes(string $path, string $phpstanConfigFile, OutputInterface $output): void
    {
        $includeLines = collect($this->phpStanSetup->includes($path))->map(fn(string $include): string => "    - {$include}");
        $withoutRulesetOption = self::WITHOUT_RULESET_OPTION;

        $output->writeln([
            "Make sure {$phpstanConfigFile} includes:",
            '',
            'includes:',
            ...$includeLines->all(),
            '',
            "Or pass --{$withoutRulesetOption} to run PHPStan without it.",
        ]);
    }
}
