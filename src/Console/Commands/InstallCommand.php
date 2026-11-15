<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;
use AdamczykPiotr\UltimateFormatter\Install\ComposerScripts;
use AdamczykPiotr\UltimateFormatter\Install\ComposerScriptsChange;
use AdamczykPiotr\UltimateFormatter\Install\FilePublisher;
use AdamczykPiotr\UltimateFormatter\Install\GitAttributes;
use AdamczykPiotr\UltimateFormatter\Install\ProjectFile;
use AdamczykPiotr\UltimateFormatter\Install\RenderedFile;
use AdamczykPiotr\UltimateFormatter\Install\StubRenderer;
use AdamczykPiotr\UltimateFormatter\PhpStan\PhpStanSetup;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'formatter:install', description: 'Publish the formatter configs into the project')]
class InstallCommand extends FormatterCommand
{
    /**
     * @param StubRenderer $stubRenderer
     * @param FilePublisher $filePublisher
     * @param ComposerScripts $composerScripts
     * @param GitAttributes $gitAttributes
     */
    public function __construct(
        protected readonly StubRenderer $stubRenderer = new StubRenderer(),
        protected readonly FilePublisher $filePublisher = new FilePublisher(),
        protected readonly ComposerScripts $composerScripts = new ComposerScripts(),
        protected readonly GitAttributes $gitAttributes = new GitAttributes(),
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite files that already exist');
        $this->addOption('ci', null, InputOption::VALUE_NONE, 'Also publish a GitHub Actions workflow that runs the check');
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
        $layout = ProjectLayout::detect($path);
        if ($layout === null) {
            $output->writeln("<error>Found neither app/ with artisan nor src/ in {$path}.</error>");

            return self::FAILURE;
        }

        $force = $input->getOption('force') === true;
        $renderedFiles = $this->stubRenderer->render($path, $layout, $input->getOption('ci') === true);
        foreach ($renderedFiles as $renderedFile) {
            $this->publishFile($path, $renderedFile, $force, $output);
        }

        $composerScriptsChange = $this->composerScripts->install($path, $layout);
        $this->writeComposerScriptsChange($composerScriptsChange, $output);

        // A package's dist archive ships to every consumer, so the dev-only files stay out of it.
        if ($layout === ProjectLayout::PACKAGE) {
            $targets = $renderedFiles->map(fn(RenderedFile $renderedFile): string => $renderedFile->file->path());
            $ignoredTargets = $this->gitAttributes->addExportIgnores($path, $targets);
            $output->writeln($ignoredTargets->isEmpty()
                ? 'Skipped .gitattributes, the published files are already export-ignored.'
                : "Added export-ignore for {$ignoredTargets->implode(', ')} to .gitattributes.");
        }

        return self::SUCCESS;
    }

    /**
     * @param string $path
     * @param RenderedFile $renderedFile
     * @param bool $force
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function publishFile(string $path, RenderedFile $renderedFile, bool $force, OutputInterface $output): void
    {
        $target = $renderedFile->file->path();
        $existingFile = $this->filePublisher->publish($path, $renderedFile, $force);
        if ($existingFile === null) {
            $output->writeln("Wrote {$target}.");

            return;
        }

        $rulesFile = PhpStanSetup::RULES_FILE;
        $hint = $renderedFile->file === ProjectFile::PHPSTAN_CONFIG ? " Include {$rulesFile} in it to run the ruleset." : '';
        $output->writeln("Skipped {$target}, {$existingFile} already exists.{$hint}");
    }

    /**
     * @param ComposerScriptsChange|null $change
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function writeComposerScriptsChange(ComposerScriptsChange|null $change, OutputInterface $output): void
    {
        if ($change === null) {
            $output->writeln('Skipped the composer scripts, composer.json does not exist.');

            return;
        }

        if ($change->isEmpty()) {
            $output->writeln('Skipped the composer scripts, they already exist.');

            return;
        }

        if ($change->addedScripts !== []) {
            $names = collect($change->addedScripts)->implode(' and ');
            $output->writeln("Added the {$names} composer scripts.");
        }

        if ($change->addedMigrateConfig) {
            $publishedFile = ConfigReader::PUBLISHED_CONFIG_FILE;
            $output->writeln("Added migrate-config to the post-update-cmd composer script, so {$publishedFile} follows each upgrade.");
        }
    }
}
