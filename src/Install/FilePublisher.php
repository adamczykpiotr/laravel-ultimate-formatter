<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

use AdamczykPiotr\UltimateFormatter\PhpStan\PhpStanSetup;
use Symfony\Component\Filesystem\Filesystem;

class FilePublisher
{
    /**
     * @param PhpStanSetup $phpStanSetup
     * @param Filesystem $filesystem
     */
    public function __construct(
        protected readonly PhpStanSetup $phpStanSetup = new PhpStanSetup(),
        protected readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Writes the file unless one is in its way, and returns the file the project ends up with.
     *
     * @param string $path
     * @param RenderedFile $renderedFile
     * @param bool $force
     *
     * @return PublishedFile
     */
    public function publish(string $path, RenderedFile $renderedFile, bool $force): PublishedFile
    {
        $target = $renderedFile->file->path();
        $existingFile = $this->existingFile($path, $renderedFile->file);

        // A phpstan.neon would shadow a .dist config, so --force only overwrites the file itself.
        $overwrites = $force && $renderedFile->file->isOverwritable() && $existingFile === $target;
        if ($existingFile !== null && $overwrites === false) {
            return new PublishedFile($existingFile, isWritten: false);
        }

        $this->filesystem->dumpFile("{$path}/{$target}", $renderedFile->contents);

        return new PublishedFile($target, isWritten: true);
    }

    /**
     * @param string $path
     * @param ProjectFile $file
     *
     * @return string|null
     */
    protected function existingFile(string $path, ProjectFile $file): string|null
    {
        if ($file === ProjectFile::PHPSTAN_CONFIG) {
            return $this->phpStanSetup->configFile($path);
        }

        $target = $file->path();

        return $this->filesystem->exists("{$path}/{$target}") ? $target : null;
    }
}
