<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

use AdamczykPiotr\UltimateFormatter\Composer\ComposerFile;
use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;
use AdamczykPiotr\UltimateFormatter\PhpStan\PhpStanSetup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Filesystem;

class StubRenderer
{
    protected const STUBS_DIRECTORY = __DIR__ . '/../../stubs';
    protected const OWN_COMPOSER_FILE = __DIR__ . '/../../composer.json';

    /**
     * @param PhpStanSetup $phpStanSetup
     * @param ComposerFile $composerFile
     * @param Filesystem $filesystem
     */
    public function __construct(
        protected readonly PhpStanSetup $phpStanSetup = new PhpStanSetup(),
        protected readonly ComposerFile $composerFile = new ComposerFile(),
        protected readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param string $path
     * @param ProjectLayout $layout
     * @param bool $ci
     *
     * @return Collection<int, RenderedFile>
     */
    public function render(string $path, ProjectLayout $layout, bool $ci): Collection
    {
        $replacements = [
            '{{fixer_paths}}' => $this->renderPhpPaths($path, $layout->fixerDirectories()),
            '{{fixer_cache_file}}' => $layout->fixerCacheFile(),
            '{{rector_paths}}' => $this->renderPhpPaths($path, $layout->rectorDirectories()),
            '{{rector_skip}}' => $this->renderRectorSkip($path, $layout->rectorSkippedDirectories()),
            '{{phpstan_includes}}' => $this->renderPhpstanIncludes($path),
            '{{phpstan_paths}}' => $this->renderNeonPaths($path, $layout->phpstanDirectories()),
        ];

        return collect(ProjectFile::cases())
            ->filter(fn(ProjectFile $file): bool => $file->isInstalledFor($layout, $ci))
            ->map(fn(ProjectFile $file): RenderedFile => new RenderedFile($file, match ($file) {
                ProjectFile::PHP_CS_FIXER_CONFIG => $this->renderStub('php-cs-fixer.php.stub', $replacements),
                ProjectFile::RECTOR_CONFIG => $this->renderStub('rector.php.stub', $replacements),
                ProjectFile::PHPSTAN_CONFIG => $this->renderStub('phpstan.neon.stub', $replacements),
                ProjectFile::GIT_BLAME_IGNORE_REVS => $this->renderStub('git-blame-ignore-revs.stub', $replacements),
                ProjectFile::FORMATTER_CONFIG => $this->filesystem->readFile(ConfigReader::DEFAULT_CONFIG_FILE),
                ProjectFile::IDE_HELPER_CONFIG => $this->renderStub('ide-helper.php.stub', $replacements),
                ProjectFile::CI_WORKFLOW => $this->renderStub('code-style.yml.stub', ['{{php_version}}' => $this->ciPhpVersion($path)]),
            }))
            ->values();
    }

    /**
     * @param string $stub
     * @param array<string, string> $replacements
     *
     * @return string
     */
    protected function renderStub(string $stub, array $replacements): string
    {
        $stubsDirectory = self::STUBS_DIRECTORY;
        $contents = $this->filesystem->readFile("{$stubsDirectory}/{$stub}");

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
        return $this->existingDirectories($path, $directories)
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
        return $this->existingDirectories($path, $directories)
            ->map(fn(string $directory): string => "        - {$directory}")
            ->implode("\n");
    }

    /**
     * @param string $path
     * @param list<string> $directories
     *
     * @return Collection<int, string>
     */
    protected function existingDirectories(string $path, array $directories): Collection
    {
        return collect($directories)->filter(fn(string $directory): bool => is_dir("{$path}/{$directory}"));
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function renderPhpstanIncludes(string $path): string
    {
        return collect($this->phpStanSetup->includes($path))
            ->map(fn(string $file): string => "    - {$file}")
            ->implode("\n");
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
        $runningVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $formatterVersion = $this->composerFile->minimumPhpVersion(self::OWN_COMPOSER_FILE) ?? $runningVersion;
        $projectVersion = $this->composerFile->minimumPhpVersion("{$path}/composer.json");

        // The formatter can't be installed below its own minimum, so CI never runs lower than that.
        if ($projectVersion === null || version_compare($projectVersion, $formatterVersion, '<')) {
            return $formatterVersion;
        }

        return $projectVersion;
    }
}
