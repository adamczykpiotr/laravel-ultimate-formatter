<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Support;

use FilesystemIterator;
use Illuminate\Support\Str;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * PHPStan can reflect a class's ancestors but not its children, so this finds them by scanning the
 * configured Rector paths for `class X extends Y` once, then confirms each candidate with reflection.
 */
final class SubclassIndex
{
    protected const NAMESPACE_PATTERN = '/\bnamespace\s+([\w\\\\]+)\s*[;{]/';
    protected const CLASS_PATTERN = '/\bclass\s+\w+\s+extends\s+\\\\?[\w\\\\]+/';
    protected const CLASS_NAME_PATTERN = '/^class\s+(\w+)/';
    protected const ALIAS_PATTERN = '/\buse\s+\\\\?([\w\\\\]+\s+as\s+\w+)\s*;/';

    /**
     * @var array<string, list<string>> candidate child class names, keyed by the lowercase short name of their parent
     */
    protected array $candidatesByParent = [];

    /**
     * @var array<string, true>
     */
    protected array $indexedFiles = [];

    protected bool $pathsIndexed = false;

    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @param ClassReflection $classReflection
     * @param string $currentFile the file being processed, indexed as well in case it is outside the paths
     *
     * @return list<ClassReflection> every class below $classReflection, at any depth
     */
    public function descendantsOf(ClassReflection $classReflection, string $currentFile): array
    {
        $this->indexPaths();
        $this->indexFile($currentFile);

        $descendants = [];
        $seen = [];
        $queue = [$classReflection];
        for ($position = 0; $position < count($queue); ++$position) {
            $parent = $queue[$position];
            foreach ($this->candidatesByParent[$this->shortName($parent->getName())] ?? [] as $candidate) {
                if (isset($seen[$candidate]) || $this->reflectionProvider->hasClass($candidate) === false) {
                    continue;
                }

                $child = $this->reflectionProvider->getClass($candidate);
                if ($child->getParentClass()?->getName() === $parent->getName()) {
                    $seen[$candidate] = true;
                    $descendants[] = $child;
                    $queue[] = $child;
                }
            }
        }

        return $descendants;
    }

    /**
     * @return void
     */
    protected function indexPaths(): void
    {
        if ($this->pathsIndexed) {
            return;
        }

        $this->pathsIndexed = true;
        foreach (SimpleParameterProvider::provideArrayParameter(Option::PATHS) as $path) {
            if (is_string($path) === false) {
                continue;
            }

            if (is_file($path)) {
                $this->indexFile($path);

                continue;
            }

            if (is_dir($path) === false) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                    $this->indexFile($file->getPathname());
                }
            }
        }
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function indexFile(string $path): void
    {
        $realPath = realpath($path);
        if ($realPath === false || isset($this->indexedFiles[$realPath])) {
            return;
        }

        $this->indexedFiles[$realPath] = true;
        $contents = @file_get_contents($realPath);
        if ($contents === false || str_contains($contents, 'extends') === false) {
            return;
        }

        // One line per declaration, so a name split over lines reads the same as one on a single line.
        $contents = Str::squish($contents);
        $namespace = Str::match(self::NAMESPACE_PATTERN, $contents);
        $namespace = $namespace === '' ? '' : "{$namespace}\\";

        // `use App\Models\User as BaseUser` followed by `extends BaseUser` is indexed under `user`.
        $aliases = Str::matchAll(self::ALIAS_PATTERN, $contents)
            ->map(fn(mixed $import): string => is_string($import) ? $import : '')
            ->mapWithKeys(fn(string $import): array => [
                $this->shortName(Str::afterLast($import, ' ')) => $this->shortName(Str::before($import, ' ')),
            ]);

        $declarations = Str::matchAll(self::CLASS_PATTERN, $contents)->map(fn(mixed $match): string => is_string($match) ? $match : '');
        foreach ($declarations as $declaration) {
            $className = Str::match(self::CLASS_NAME_PATTERN, $declaration);
            $parentShortName = $this->shortName(Str::afterLast($declaration, ' '));
            $this->candidatesByParent[$aliases->get($parentShortName, $parentShortName)][] = "{$namespace}{$className}";
        }
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function shortName(string $className): string
    {
        return Str::lower(Str::afterLast($className, '\\'));
    }
}
