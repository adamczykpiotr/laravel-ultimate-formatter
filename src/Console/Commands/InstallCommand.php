<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'formatter:install', description: 'Publish the formatter configs into the project')]
class InstallCommand extends FormatterCommand
{
    protected const STUBS_DIRECTORY = __DIR__ . '/../../../stubs';

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite files that already exist');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
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
        $filesystem = new Filesystem();

        foreach ($this->renderFiles($path, $layout) as $target => $contents) {
            $targetPath = "{$path}/{$target}";
            if ($force === false && $filesystem->exists($targetPath)) {
                $output->writeln("Skipped {$target}, it already exists (use --force to overwrite).");

                continue;
            }

            $filesystem->dumpFile($targetPath, $contents);
            $output->writeln("Wrote {$target}.");
        }

        return self::SUCCESS;
    }

    /**
     * @param string $path
     * @param ProjectLayout $layout
     * @return array<string, string>
     */
    protected function renderFiles(string $path, ProjectLayout $layout): array
    {
        $replacements = [
            '{{fixer_paths}}' => $this->renderPhpPaths($path, $layout->fixerDirectories()),
            '{{fixer_cache_file}}' => $layout->fixerCacheFile(),
            '{{rector_paths}}' => $this->renderPhpPaths($path, $layout->rectorDirectories()),
            '{{rector_skip}}' => $this->renderRectorSkip($path, $layout->rectorSkippedDirectories()),
        ];

        $files = [
            '.php-cs-fixer.php' => $this->renderStub('php-cs-fixer.php.stub', $replacements),
            'rector.php' => $this->renderStub('rector.php.stub', $replacements),
            '.git-blame-ignore-revs' => $this->renderStub('git-blame-ignore-revs.stub', $replacements),
        ];

        // A package's config/ directory holds the configs it ships, so only an app gets this one.
        if ($layout === ProjectLayout::APP) {
            $files[self::PUBLISHED_CONFIG_FILE] = (new Filesystem())->readFile(self::DEFAULT_CONFIG_FILE);
        }

        return $files;
    }

    /**
     * @param string $stub
     * @param array<string, string> $replacements
     * @return string
     */
    protected function renderStub(string $stub, array $replacements): string
    {
        $contents = (new Filesystem())->readFile(self::STUBS_DIRECTORY . "/{$stub}");

        return Str::swap($replacements, $contents);
    }

    /**
     * @param string $path
     * @param list<string> $directories
     * @return string
     */
    protected function renderPhpPaths(string $path, array $directories): string
    {
        return collect($directories)
            ->filter(fn(string $directory): bool => is_dir("{$path}/{$directory}"))
            ->map(fn(string $directory): string => "        __DIR__ . '/{$directory}',")
            ->implode("\n");
    }

    /**
     * @param string $path
     * @param list<string> $directories
     * @return string
     */
    protected function renderRectorSkip(string $path, array $directories): string
    {
        $skippedPaths = $this->renderPhpPaths($path, $directories);
        if ($skippedPaths === '') {
            return '';
        }

        return "\n    ->withSkip([\n{$skippedPaths}\n    ])";
    }
}
