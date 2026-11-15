<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'formatter:check', description: 'Dry-run Rector and PHP CS Fixer, then run PHPStan, failing on any pending change or error')]
class CheckCommand extends FormatterCommand
{
    protected const PHPSTAN_SCRIPT = 'vendor/bin/phpstan';

    // phpstan-rules.neon declares this parameter, so PHPStan only knows it when the ruleset is loaded.
    protected const RULESET_PARAMETER = 'ultimateFormatterConfigFile';
    protected const WITHOUT_RULESET_OPTION = 'without-phpstan-ruleset';

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
        // Without the ruleset PHPStan passes code the ruleset bans, so check it before spending time on the formatters.
        $phpstanConfigFile = $this->phpstanConfigFile($this->projectPath($input));
        $requiresRuleset = $phpstanConfigFile !== null && $input->getOption(self::WITHOUT_RULESET_OPTION) === false;
        if ($requiresRuleset && $this->loadsRuleset($phpstanConfigFile, $input, $output) === false) {
            return self::FAILURE;
        }

        // The fixer would check code Rector still has to change, so stop at the first failure like `format` does.
        $rectorExitCode = $this->runPhpScript(self::RECTOR_SCRIPT, $this->configuredArguments($input, 'check.rector'), $input, $output);
        if ($rectorExitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        $fixerExitCode = $this->runPhpScript('vendor/bin/php-cs-fixer', $this->configuredArguments($input, 'check.php-cs-fixer'), $input, $output);
        if ($fixerExitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        // Without a config PHPStan has no paths to analyse, so a project that never published one keeps the formatting check.
        if ($phpstanConfigFile === null) {
            $output->writeln('Skipped PHPStan, the project has no phpstan.neon.');

            return self::SUCCESS;
        }

        $phpstanExitCode = $this->runPhpScript(self::PHPSTAN_SCRIPT, $this->configuredArguments($input, 'check.phpstan'), $input, $output);
        if ($phpstanExitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Asks PHPStan for its resolved parameters, so a ruleset included through another neon file counts too.
     *
     * @param string $phpstanConfigFile
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function loadsRuleset(string $phpstanConfigFile, InputInterface $input, OutputInterface $output): bool
    {
        $process = new Process([PHP_BINARY, self::PHPSTAN_SCRIPT, 'dump-parameters', '--json'], $this->projectPath($input), timeout: null);
        $process->run();
        if ($process->isSuccessful() === false) {
            $output->writeln("<error>PHPStan could not load {$phpstanConfigFile}:</error>");
            $output->writeln(trim($process->getErrorOutput() . $process->getOutput()));
            $this->writeRulesetIncludes($phpstanConfigFile, $input, $output);

            return false;
        }

        $parameters = json_decode($process->getOutput(), true);
        if (is_array($parameters) && Arr::has($parameters, self::RULESET_PARAMETER)) {
            return true;
        }

        $output->writeln("<error>{$phpstanConfigFile} doesn't load the formatter's PHPStan ruleset, so its rules never run.</error>");
        $this->writeRulesetIncludes($phpstanConfigFile, $input, $output);

        return false;
    }

    /**
     * @param string $phpstanConfigFile
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function writeRulesetIncludes(string $phpstanConfigFile, InputInterface $input, OutputInterface $output): void
    {
        $output->writeln("Make sure {$phpstanConfigFile} includes:");
        $output->writeln('');
        $output->writeln('includes:');
        foreach ($this->phpstanIncludes($this->projectPath($input)) as $include) {
            $output->writeln("    - {$include}");
        }
        $output->writeln('');
        $output->writeln('Or pass --' . self::WITHOUT_RULESET_OPTION . ' to run PHPStan without it.');
    }
}
