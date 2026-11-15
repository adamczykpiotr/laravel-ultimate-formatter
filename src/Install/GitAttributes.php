<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Filesystem;

class GitAttributes
{
    protected const ATTRIBUTES_FILE = '.gitattributes';
    protected const EXPORT_IGNORE = 'export-ignore';

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        protected readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Returns the targets it marked, leaving out the ones already export-ignored.
     *
     * @param string $path
     * @param Collection<int, string> $targets
     *
     * @return Collection<int, string>
     */
    public function addExportIgnores(string $path, Collection $targets): Collection
    {
        $attributesFileName = self::ATTRIBUTES_FILE;
        $attributesFile = "{$path}/{$attributesFileName}";
        $contents = $this->filesystem->exists($attributesFile) ? $this->filesystem->readFile($attributesFile) : '';
        $ignoredPaths = $this->exportIgnoredPaths($contents);

        $missingTargets = $targets
            ->reject(fn(string $target): bool => $ignoredPaths->contains(
                fn(string $ignoredPath): bool => $target === $ignoredPath || str_starts_with($target, "{$ignoredPath}/"),
            ))
            ->values();
        if ($missingTargets->isEmpty()) {
            return $missingTargets;
        }

        $exportIgnore = self::EXPORT_IGNORE;
        $lines = $missingTargets->map(fn(string $target): string => "/{$target} {$exportIgnore}")->implode("\n");
        $separator = $contents === '' || str_ends_with($contents, "\n") ? '' : "\n";
        $this->filesystem->dumpFile($attributesFile, "{$contents}{$separator}{$lines}\n");

        return $missingTargets;
    }

    /**
     * @param string $contents
     *
     * @return Collection<int, string>
     */
    protected function exportIgnoredPaths(string $contents): Collection
    {
        return Str::of($contents)
            ->split('/\R/')
            ->map(fn(string $line): string => trim($line))
            ->reject(fn(string $line): bool => $line === '' || str_starts_with($line, '#'))
            ->map(fn(string $line): Collection => Str::of($line)->split('/\s+/'))
            ->filter(fn(Collection $tokens): bool => $tokens->skip(1)->contains(self::EXPORT_IGNORE))
            ->map(fn(Collection $tokens): string => trim($tokens->first() ?? '', '/'))
            ->values();
    }
}
