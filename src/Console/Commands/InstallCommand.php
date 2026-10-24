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

#[AsCommand(name: 'formatter:install', description: 'Publish the formatter configs, editorconfig and CI workflow into the project')]
class InstallCommand extends FormatterCommand
{
    protected const STUBS_DIRECTORY = __DIR__ . '/../../../stubs';
    protected const EXTENSION_INSTALLER_PACKAGE = 'phpstan/extension-installer';

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
        $phpstanConfigFile = $layout->phpstanConfigFile();
        $replacements = [
            '{{fixer_paths}}' => $this->renderPhpPaths($path, $layout->fixerDirectories()),
            '{{fixer_cache_file}}' => $layout->fixerCacheFile(),
            '{{rector_paths}}' => $this->renderPhpPaths($path, $layout->rectorDirectories()),
            '{{rector_skip}}' => $this->renderRectorSkip($path, $layout->rectorSkippedDirectories()),
            '{{larastan_include}}' => $this->renderLarastanInclude($path),
            '{{phpstan_paths}}' => $this->renderNeonPaths($path, $layout->phpstanDirectories()),
            '{{phpstan_config_file}}' => $phpstanConfigFile,
        ];

        return [
            '.php-cs-fixer.php' => $this->renderStub('php-cs-fixer.php.stub', $replacements),
            'rector.php' => $this->renderStub('rector.php.stub', $replacements),
            $phpstanConfigFile => $this->renderStub('phpstan.neon.stub', $replacements),
            '.editorconfig' => $this->renderStub('editorconfig.stub', $replacements),
            '.github/workflows/code-style.yml' => $this->renderStub('code-style.yml.stub', $replacements),
            '.git-blame-ignore-revs' => $this->renderStub('git-blame-ignore-revs.stub', $replacements),
        ];
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

    /**
     * @param string $path
     * @param list<string> $directories
     * @return string
     */
    protected function renderNeonPaths(string $path, array $directories): string
    {
        return collect($directories)
            ->filter(fn(string $directory): bool => is_dir("{$path}/{$directory}"))
            ->map(fn(string $directory): string => "        - {$directory}")
            ->implode("\n");
    }

    /**
     * @param string $path
     * @return string
     */
    protected function renderLarastanInclude(string $path): string
    {
        // The extension installer already includes Larastan, and PHPStan rejects a second include.
        if ($this->requiresPackage($path, self::EXTENSION_INSTALLER_PACKAGE)) {
            return '';
        }

        return "    - vendor/larastan/larastan/extension.neon\n";
    }

    /**
     * @param string $path
     * @param string $package
     * @return bool
     */
    protected function requiresPackage(string $path, string $package): bool
    {
        $composerFile = "{$path}/composer.json";
        $filesystem = new Filesystem();
        if ($filesystem->exists($composerFile) === false) {
            return false;
        }

        $composer = json_decode($filesystem->readFile($composerFile), associative: true, flags: JSON_THROW_ON_ERROR);

        return data_get($composer, "require.{$package}") !== null
            || data_get($composer, "require-dev.{$package}") !== null;
    }
}
