<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config\Diff;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Filesystem;

class DiffParser
{
    protected const HUNK_HEADER_PREFIX = '@@';

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        protected readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param string $diffFile
     *
     * @return Collection<int, Collection<int, DiffLine>>
     */
    public function hunks(string $diffFile): Collection
    {
        $contents = $this->filesystem->readFile($diffFile);

        // The file headers come before the first hunk.
        return Str::of($contents)
            ->explode("\n")
            ->skipUntil(fn(string $line): bool => $this->isHunkHeader($line))
            ->chunkWhile(fn(string $line): bool => $this->isHunkHeader($line) === false)
            ->map(fn(Collection $hunkLines): Collection => $hunkLines
                ->skip(1)
                ->map(fn(string $line): DiffLine|null => DiffLine::parse($line))
                ->filter()
                ->values())
            ->values();
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    protected function isHunkHeader(string $line): bool
    {
        return str_starts_with($line, self::HUNK_HEADER_PREFIX);
    }
}
