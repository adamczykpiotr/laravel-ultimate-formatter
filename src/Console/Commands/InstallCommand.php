<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console\Commands;

use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'formatter:install', description: 'Publish the formatter configs into the project')]
class InstallCommand extends FormatterCommand
{
    protected const STUBS_DIRECTORY = __DIR__ . '/../../../stubs';
    protected const OWN_COMPOSER_FILE = __DIR__ . '/../../../composer.json';
    protected const BLAME_IGNORE_REVS_FILE = '.git-blame-ignore-revs';
    protected const CI_WORKFLOW_FILE = '.github/workflows/code-style.yml';
    protected const PHPSTAN_CONFIG_FILE = 'phpstan.neon';
    protected const IDE_HELPER_CONFIG_FILE = 'config/ide-helper.php';
    protected const COMPOSER_SCRIPTS = [
        'check' => 'vendor/bin/ultimate-formatter check',
        'format' => 'vendor/bin/ultimate-formatter format',
    ];
    protected const MIGRATE_CONFIG_SCRIPT = 'vendor/bin/ultimate-formatter migrate-config';

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
        $filesystem = new Filesystem();
        $files = $this->renderFiles($path, $layout, $input->getOption('ci') === true);

        foreach ($files as $target => $contents) {
            $existingFile = $this->existingFile($path, $target);
            // The blame ignore file holds recorded commit SHAs, and a phpstan.neon would shadow a .dist config, so --force keeps both.
            $keepsExisting = $force === false || $target === self::BLAME_IGNORE_REVS_FILE || $existingFile !== $target;
            if ($keepsExisting && $existingFile !== null) {
                $hint = $target === self::PHPSTAN_CONFIG_FILE ? ' Include ' . self::PHPSTAN_RULES_FILE . ' in it to run the ruleset.' : '';
                $output->writeln("Skipped {$target}, {$existingFile} already exists.{$hint}");

                continue;
            }

            $filesystem->dumpFile("{$path}/{$target}", $contents);
            $output->writeln("Wrote {$target}.");
        }

        $this->addComposerScripts($path, $layout, $output);

        // A package's dist archive ships to every consumer, so the dev-only files stay out of it.
        if ($layout === ProjectLayout::PACKAGE) {
            $this->addExportIgnores($path, collect($files)->keys(), $output);
        }

        return self::SUCCESS;
    }

    /**
     * @param string $path
     * @param ProjectLayout $layout
     * @param bool $ci
     *
     * @return array<string, string>
     */
    protected function renderFiles(string $path, ProjectLayout $layout, bool $ci): array
    {
        $replacements = [
            '{{fixer_paths}}' => $this->renderPhpPaths($path, $layout->fixerDirectories()),
            '{{fixer_cache_file}}' => $layout->fixerCacheFile(),
            '{{rector_paths}}' => $this->renderPhpPaths($path, $layout->rectorDirectories()),
            '{{rector_skip}}' => $this->renderRectorSkip($path, $layout->rectorSkippedDirectories()),
            '{{phpstan_includes}}' => $this->renderPhpstanIncludes($path),
            '{{phpstan_paths}}' => $this->renderNeonPaths($path, $layout->phpstanDirectories()),
        ];

        $files = [
            '.php-cs-fixer.php' => $this->renderStub('php-cs-fixer.php.stub', $replacements),
            'rector.php' => $this->renderStub('rector.php.stub', $replacements),
            self::PHPSTAN_CONFIG_FILE => $this->renderStub('phpstan.neon.stub', $replacements),
            self::BLAME_IGNORE_REVS_FILE => $this->renderStub('git-blame-ignore-revs.stub', $replacements),
        ];

        // A package's config/ directory holds the configs it ships, so only an app gets these.
        if ($layout === ProjectLayout::APP) {
            $files[self::PUBLISHED_CONFIG_FILE] = (new Filesystem())->readFile(self::DEFAULT_CONFIG_FILE);
            $files[self::IDE_HELPER_CONFIG_FILE] = $this->renderStub('ide-helper.php.stub', $replacements);
        }

        if ($ci) {
            $files[self::CI_WORKFLOW_FILE] = $this->renderStub('code-style.yml.stub', ['{{php_version}}' => $this->ciPhpVersion($path)]);
        }

        return $files;
    }

    /**
     * @param string $stub
     * @param array<string, string> $replacements
     *
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
     *
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
     *
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
     *
     * @return string
     */
    protected function renderPhpstanIncludes(string $path): string
    {
        return collect($this->phpstanIncludes($path))
            ->map(fn(string $file): string => "    - {$file}")
            ->implode("\n");
    }

    /**
     * @param string $path
     * @param string $target
     *
     * @return string|null
     */
    protected function existingFile(string $path, string $target): string|null
    {
        if ($target === self::PHPSTAN_CONFIG_FILE) {
            return $this->phpstanConfigFile($path);
        }

        return is_file("{$path}/{$target}") ? $target : null;
    }

    /**
     * @param string $path
     * @param list<string> $directories
     *
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
     * CI runs on the lowest PHP the project supports, so PHP CS Fixer can't introduce newer syntax.
     *
     * @param string $path
     *
     * @return string
     */
    protected function ciPhpVersion(string $path): string
    {
        $formatterVersion = $this->minimumPhpVersion(self::OWN_COMPOSER_FILE) ?? PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $projectVersion = $this->minimumPhpVersion("{$path}/composer.json");

        // The formatter can't be installed below its own minimum, so CI never runs lower than that.
        if ($projectVersion === null || version_compare($projectVersion, $formatterVersion, '<')) {
            return $formatterVersion;
        }

        return $projectVersion;
    }

    /**
     * @param string $composerFile
     *
     * @return string|null
     */
    protected function minimumPhpVersion(string $composerFile): string|null
    {
        $constraint = data_get($this->readComposerFile($composerFile), 'require.php');
        if (is_string($constraint) === false) {
            return null;
        }

        // Each version in the constraint, such as 8.3 in ^8.3.1, cut down to its major and minor.
        return Str::of($constraint)
            ->split('/[^\d.]+/')
            ->map(fn(string $part): string => Str::match('/\d+\.\d+/', $part))
            ->reject(fn(string $version): bool => $version === '')
            ->sort(fn(string $first, string $second): int => version_compare($first, $second))
            ->first();
    }

    /**
     * @param string $path
     * @param ProjectLayout $layout
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function addComposerScripts(string $path, ProjectLayout $layout, OutputInterface $output): void
    {
        $composerFile = "{$path}/composer.json";
        $composer = $this->readComposerFile($composerFile);
        if ($composer === null) {
            $output->writeln('Skipped the composer scripts, composer.json does not exist.');

            return;
        }

        $scripts = data_get($composer, 'scripts');
        // An empty "scripts": [] decodes to an array, so it is replaced with an object.
        $scripts = $scripts instanceof stdClass ? $scripts : new stdClass();
        $missingScripts = collect(self::COMPOSER_SCRIPTS)->reject(fn(string $command, string $name): bool => property_exists($scripts, $name));
        foreach ($missingScripts as $name => $command) {
            $scripts->{$name} = $command;
        }

        // Only an app publishes config/ultimate-formatter.php, so only an app migrates it after each upgrade.
        $addsMigrateConfig = $layout === ProjectLayout::APP && $this->addMigrateConfigScript($scripts);
        if ($missingScripts->isEmpty() && $addsMigrateConfig === false) {
            $output->writeln('Skipped the composer scripts, they already exist.');

            return;
        }

        $composer->scripts = $scripts;
        // The flags match Composer's own, so the file keeps its usual formatting.
        $contents = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        (new Filesystem())->dumpFile($composerFile, "{$contents}\n");

        if ($missingScripts->isNotEmpty()) {
            $names = $missingScripts->keys()->implode(' and ');
            $output->writeln("Added the {$names} composer scripts.");
        }

        if ($addsMigrateConfig === true) {
            $publishedFile = self::PUBLISHED_CONFIG_FILE;
            $output->writeln("Added migrate-config to the post-update-cmd composer script, so {$publishedFile} follows each upgrade.");
        }
    }

    /**
     * @param stdClass $scripts
     *
     * @return bool
     */
    protected function addMigrateConfigScript(stdClass $scripts): bool
    {
        $hook = $scripts->{'post-update-cmd'} ?? [];
        // Composer takes a single command as a string, or a list of them.
        $commands = is_array($hook) ? $hook : [$hook];
        if (in_array(self::MIGRATE_CONFIG_SCRIPT, $commands, true)) {
            return false;
        }

        $scripts->{'post-update-cmd'} = [...$commands, self::MIGRATE_CONFIG_SCRIPT];

        return true;
    }

    /**
     * @param string $path
     * @param Collection<int, string> $targets
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function addExportIgnores(string $path, Collection $targets, OutputInterface $output): void
    {
        $filesystem = new Filesystem();
        $attributesFile = "{$path}/.gitattributes";
        $contents = $filesystem->exists($attributesFile) ? $filesystem->readFile($attributesFile) : '';
        $ignoredPaths = $this->exportIgnoredPaths($contents);

        $missingTargets = $targets
            ->reject(fn(string $target): bool => collect($ignoredPaths)->contains(
                fn(string $ignoredPath): bool => $target === $ignoredPath || str_starts_with($target, "{$ignoredPath}/"),
            ))
            ->values();
        if ($missingTargets->isEmpty()) {
            $output->writeln('Skipped .gitattributes, the published files are already export-ignored.');

            return;
        }

        $lines = $missingTargets->map(fn(string $target): string => "/{$target} export-ignore")->implode("\n");
        $separator = $contents === '' || str_ends_with($contents, "\n") ? '' : "\n";
        $filesystem->dumpFile($attributesFile, "{$contents}{$separator}{$lines}\n");

        $output->writeln("Added export-ignore for {$missingTargets->implode(', ')} to .gitattributes.");
    }

    /**
     * @param string $contents
     *
     * @return array<int, string>
     */
    protected function exportIgnoredPaths(string $contents): array
    {
        return Str::of($contents)
            ->split('/\R/')
            ->map(fn(string $line): string => trim($line))
            ->reject(fn(string $line): bool => $line === '' || str_starts_with($line, '#'))
            ->map(fn(string $line): Collection => Str::of($line)->split('/\s+/'))
            ->filter(fn(Collection $tokens): bool => $tokens->skip(1)->contains('export-ignore'))
            ->map(fn(Collection $tokens): string => trim($tokens->first() ?? '', '/'))
            ->values()
            ->all();
    }
}
