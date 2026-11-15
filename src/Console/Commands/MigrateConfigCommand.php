<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Config\ConfigMigrator;
use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use AdamczykPiotr\UltimateFormatter\Exceptions\ConfigNotMigratableException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

// Composer's post-update-cmd runs it, so it stays out of the command lists.
#[AsCommand(name: 'formatter:migrate-config', description: 'Bring the published config/ultimate-formatter.php up to date with the package', hidden: true)]
class MigrateConfigCommand extends FormatterCommand
{
    /**
     * @param ConfigMigrator $migrator
     * @param ConfigReader $configReader
     */
    public function __construct(
        protected readonly ConfigMigrator $migrator = new ConfigMigrator(),
        protected readonly ConfigReader $configReader = new ConfigReader(),
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the changes without writing them');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $publishedFile = ConfigReader::PUBLISHED_CONFIG_FILE;
        $configFile = $this->configReader->publishedConfigFile($this->projectPath($input));
        if (is_file($configFile) === false) {
            $output->writeln("Skipped the config migration, {$publishedFile} is not published.", OutputInterface::VERBOSITY_VERBOSE);

            return self::SUCCESS;
        }

        $dryRun = $input->getOption('dry-run') === true;

        try {
            $migration = $this->migrator->migrate($configFile, $dryRun);
        } catch (ConfigNotMigratableException|IOExceptionInterface $exception) {
            // It runs after composer install and update, so a config it can't read, parse or write must not fail them.
            $reason = rtrim($exception->getMessage(), '.');
            $output->writeln("<comment>Skipped the config migration, {$publishedFile} can't be edited: {$reason}.</comment>");

            return self::SUCCESS;
        }

        if ($migration->isUpToDate()) {
            $output->writeln("{$publishedFile} is up to date.", OutputInterface::VERBOSITY_VERBOSE);

            return self::SUCCESS;
        }

        foreach ($migration->applied as $message) {
            $output->writeln($message);
        }

        foreach ($migration->skipped as $message) {
            $output->writeln("<comment>{$message}</comment>");
        }

        $result = $dryRun === true ? 'Would migrate' : 'Migrated';
        $versions = $migration->fromVersion === $migration->toVersion
            ? "to version {$migration->toVersion}"
            : "from version {$migration->fromVersion} to {$migration->toVersion}";
        $output->writeln("{$result} {$publishedFile} {$versions}.");

        return self::SUCCESS;
    }
}
