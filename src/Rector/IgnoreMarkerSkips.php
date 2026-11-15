<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector;

use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;
use AdamczykPiotr\UltimateFormatter\Ignore\IgnoreMarkers;
use Composer\InstalledVersions;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

/**
 * Turns `// formatter-ignore <ShortName>Rector` markers into Rector skips. Rector offers no hook between a rule and
 * the node it rewrites, so a marker skips its rule for the whole file, not just the line.
 */
class IgnoreMarkerSkips
{
    /**
     * The packages whose rules the config enables, so a short class name resolves to the class Rector skips.
     */
    protected const RULE_PACKAGES = [
        'rector/rector' => ['rules', 'src'],
        'driftingly/rector-laravel' => ['src'],
    ];

    /**
     * @param string $path The project root.
     *
     * @return array<class-string, list<string>> Rule => the files it skips.
     */
    public static function find(string $path): array
    {
        $directories = collect(ProjectLayout::detect($path)?->rectorDirectories() ?? [])
            ->map(fn(string $directory): string => "{$path}/{$directory}")
            ->filter(fn(string $directory): bool => is_dir($directory));

        if ($directories->isEmpty()) {
            return [];
        }

        $files = Finder::create()->files()->name('*.php')->in($directories->all())->contains(IgnoreMarkers::MARKER);

        $skips = [];
        $classes = [];
        foreach ($files as $file) {
            $markers = IgnoreMarkers::fromCode($file->getContents());
            $rules = collect($markers->identifiers())->filter(fn(string $identifier): bool => IgnoreMarkers::isRectorIdentifier($identifier));

            foreach ($rules as $rule) {
                $classes[$rule] ??= self::resolve($rule, $file->getRealPath());
                $skips[$classes[$rule]][] = $file->getRealPath();
            }
        }

        return $skips;
    }

    /**
     * @param string $rule
     * @param string $file
     *
     * @return class-string
     */
    protected static function resolve(string $rule, string $file): string
    {
        $ownRule = "AdamczykPiotr\\UltimateFormatter\\Rector\\Rules\\{$rule}";
        if (class_exists($ownRule)) {
            return $ownRule;
        }

        $directories = collect(self::RULE_PACKAGES)
            ->flatMap(fn(array $directories, string $package): array => collect($directories)
                ->map(fn(string $directory): string => self::installPath($package) . "/{$directory}")
                ->all())
            ->filter(fn(string $directory): bool => is_dir($directory));

        foreach (Finder::create()->files()->name("{$rule}.php")->in($directories->all()) as $candidate) {
            $namespace = Str::betweenFirst($candidate->getContents(), "\nnamespace ", ';');

            /** @var class-string $class */
            $class = "{$namespace}\\{$rule}";

            return $class;
        }

        throw new UnexpectedValueException("A formatter-ignore marker in {$file} names an unknown Rector rule: {$rule}.");
    }

    /**
     * Rector's scoped vendor registers its own installed packages, which list rector/rector without a path, so
     * `InstalledVersions::getInstallPath()` returns null whenever that set loads first.
     *
     * @param string $package
     *
     * @return string|null
     */
    protected static function installPath(string $package): string|null
    {
        return collect(InstalledVersions::getAllRawData())
            ->map(fn(array $installed): string|null => $installed['versions'][$package]['install_path'] ?? null)
            ->filter()
            ->first();
    }
}
