<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Config\ConfigMigrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

#[AsCommand(name: 'formatter:migrate-config', description: 'Bring the published config/ultimate-formatter.php up to date with the package')]
class MigrateConfigCommand extends FormatterCommand
{
    /**
     * @param ConfigMigrator $migrator
     */
    public function __construct(
        protected readonly ConfigMigrator $migrator = new ConfigMigrator(),
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
     * It runs from composer's post-update-cmd, so a config it can't migrate is reported without failing the update.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = "{$this->projectPath($input)}/" . self::PUBLISHED_CONFIG_FILE;
        if (is_file($configFile) === false) {
            $output->writeln('Skipped the config migration, ' . self::PUBLISHED_CONFIG_FILE . ' is not published.', OutputInterface::VERBOSITY_VERBOSE);

            return self::SUCCESS;
        }

        $dryRun = $input->getOption('dry-run') === true;

        try {
            $migration = $this->migrator->migrate($configFile, $dryRun);
        } catch (UnexpectedValueException $exception) {
            $output->writeln('<comment>Skipped the config migration, ' . self::PUBLISHED_CONFIG_FILE . " can't be edited: {$exception->getMessage()}.</comment>");

            return self::SUCCESS;
        }

        if ($migration->isUpToDate()) {
            $output->writeln(self::PUBLISHED_CONFIG_FILE . ' is up to date.', OutputInterface::VERBOSITY_VERBOSE);

            return self::SUCCESS;
        }

        foreach ($migration->applied as $message) {
            $output->writeln($message);
        }

        foreach ($migration->skipped as $message) {
            $output->writeln("<comment>{$message}</comment>");
        }

        $result = $dryRun ? 'Would migrate' : 'Migrated';
        $output->writeln("{$result} " . self::PUBLISHED_CONFIG_FILE . " from version {$migration->fromVersion} to {$migration->toVersion}.");

        return self::SUCCESS;
    }
}
