<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Composer;

use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

class ComposerFile
{
    // The flags match Composer's own, so the file keeps its usual formatting.
    protected const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        protected readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param string $composerFile
     *
     * @return stdClass|null
     */
    public function read(string $composerFile): stdClass|null
    {
        if ($this->filesystem->exists($composerFile) === false) {
            return null;
        }

        // Objects keep empty {} sections intact when the file is written back.
        $contents = $this->filesystem->readFile($composerFile);
        $composer = json_decode($contents, flags: JSON_THROW_ON_ERROR);
        if ($composer instanceof stdClass === false) {
            throw new UnexpectedValueException("{$composerFile} must contain a JSON object.");
        }

        return $composer;
    }

    /**
     * @param string $composerFile
     * @param stdClass $composer
     *
     * @return void
     */
    public function write(string $composerFile, stdClass $composer): void
    {
        $contents = json_encode($composer, self::JSON_FLAGS);

        $this->filesystem->dumpFile($composerFile, "{$contents}\n");
    }

    /**
     * @param string $composerFile
     * @param string $package
     *
     * @return bool
     */
    public function requires(string $composerFile, string $package): bool
    {
        $composer = $this->read($composerFile);

        return collect(['require', 'require-dev'])
            ->contains(fn(string $section): bool => isset($this->section($composer, $section)->{$package}));
    }

    /**
     * @param string $composerFile
     *
     * @return string|null
     */
    public function minimumPhpVersion(string $composerFile): string|null
    {
        $constraint = $this->section($this->read($composerFile), 'require')->php ?? null;
        if (is_string($constraint) === false) {
            return null;
        }

        return Str::of($constraint)
            ->split('/[^\d.]+/')
            ->map(fn(string $part): string => Str::match('/\d+\.\d+/', $part))
            ->reject(fn(string $version): bool => $version === '')
            ->sort(fn(string $first, string $second): int => version_compare($first, $second))
            ->first();
    }

    /**
     * @param stdClass|null $composer
     * @param string $name
     *
     * @return stdClass|null
     */
    protected function section(stdClass|null $composer, string $name): stdClass|null
    {
        $section = $composer->{$name} ?? null;

        return $section instanceof stdClass ? $section : null;
    }
}
