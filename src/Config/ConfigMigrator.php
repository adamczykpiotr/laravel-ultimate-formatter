<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

class ConfigMigrator
{
    public const DIFFS_DIRECTORY = __DIR__ . '/../../config/diffs';
    protected const VERSION_PATTERN = "/^\\s*'config-version'\\s*=>\\s*'([^']*)'\\s*,\\s*$/";
    protected const INITIAL_VERSION = '0.0.0';
    protected const KEY_PATTERN = "/^\\s*'([^']+)'\\s*=>/";
    protected const VALUE_PATTERN = "/^\\s*'[^']+'\\s*=>(.*)$/";
    protected const RETURN_LINE = 'return [';

    /**
     * @param string $diffsDirectory
     */
    public function __construct(
        protected readonly string $diffsDirectory = self::DIFFS_DIRECTORY,
    ) {}

    /**
     * @return string
     */
    public function latestVersion(): string
    {
        return $this->versions()->last() ?? self::INITIAL_VERSION;
    }

    /**
     * @param string $configFile
     * @param bool $dryRun
     *
     * @return ConfigMigration
     */
    public function migrate(string $configFile, bool $dryRun = false): ConfigMigration
    {
        $filesystem = new Filesystem();
        $lines = collect(explode("\n", $filesystem->readFile($configFile)));
        $isVersioned = $this->publishedVersion($lines) !== null;
        $fromVersion = $this->publishedVersion($lines) ?? $this->unversionedVersion($lines);
        $toVersion = $this->latestVersion();
        if ($isVersioned && version_compare($fromVersion, $toVersion, '>=')) {
            return new ConfigMigration($fromVersion, $fromVersion);
        }

        // The diffs leave the version line out, so it is lifted out while they apply and written back after.
        $lines = $lines->reject(fn(string $line): bool => Str::isMatch(self::VERSION_PATTERN, $line))->values();
        $returnLine = $lines->search(fn(string $line): bool => trim($line) === self::RETURN_LINE);
        if (is_int($returnLine) === false) {
            throw new UnexpectedValueException('it does not return a [...] array');
        }

        $applied = [];
        $skipped = [];
        $versions = $this->versions()->filter(fn(string $version): bool => version_compare($version, $fromVersion, '>'));
        foreach ($versions as $version) {
            foreach ($this->hunks("{$this->diffsDirectory}/{$version}.diff") as $hunk) {
                $result = $this->applyHunk($lines, $hunk);
                if ($result === null) {
                    $hunkText = collect($hunk)->map(fn(array $line): string => "{$line[0]}{$line[1]}")->implode("\n");
                    $skipped[] = "Skipped a change from config/diffs/{$version}.diff, the lines around it were edited:\n{$hunkText}";

                    continue;
                }

                [$lines, $messages] = $result;
                $applied = [...$applied, ...$messages];
            }
        }

        $lines = $this->withVersion($lines, $toVersion);
        if ($dryRun === false) {
            $filesystem->dumpFile($configFile, $lines->implode("\n"));
        }

        return new ConfigMigration($fromVersion, $toVersion, $applied, $skipped);
    }

    /**
     * @return Collection<int, string>
     */
    protected function versions(): Collection
    {
        return collect(glob("{$this->diffsDirectory}/*.diff") ?: [])
            ->map(fn(string $file): string => basename($file, '.diff'))
            ->sort(fn(string $first, string $second): int => version_compare($first, $second))
            ->values();
    }

    /**
     * @param Collection<int, string> $lines
     *
     * @return string|null
     */
    protected function publishedVersion(Collection $lines): string|null
    {
        $versionLine = $lines->first(fn(string $line): bool => Str::isMatch(self::VERSION_PATTERN, $line));

        return is_string($versionLine) ? Str::match(self::VERSION_PATTERN, $versionLine) : null;
    }

    /**
     * A config published before config-version existed is dated by its keys: the newest release whose added keys are
     * all there, so every later diff still applies to it.
     *
     * @param Collection<int, string> $lines
     *
     * @return string
     */
    protected function unversionedVersion(Collection $lines): string
    {
        $publishedKeys = $lines->map(fn(string $line): string|null => $this->key($line))->filter()->values();

        return $this->versions()
            ->filter(function (string $version) use ($publishedKeys): bool {
                $addedKeys = $this->addedKeys("{$this->diffsDirectory}/{$version}.diff");

                return $addedKeys->isNotEmpty() && $addedKeys->diff($publishedKeys)->isEmpty();
            })
            ->last() ?? self::INITIAL_VERSION;
    }

    /**
     * @param string $diffFile
     *
     * @return Collection<int, non-falsy-string>
     */
    protected function addedKeys(string $diffFile): Collection
    {
        $lines = collect($this->hunks($diffFile))->flatMap(fn(array $hunk): array => $hunk);
        $keysOf = fn(string $operation): Collection => $lines
            ->filter(fn(array $line): bool => $line[0] === $operation)
            ->map(fn(array $line): string|null => $this->key($line[1]))
            ->filter();

        return $keysOf('+')->diff($keysOf('-'))->unique()->values();
    }

    /**
     * @param Collection<int, string> $lines
     * @param string $version
     *
     * @return Collection<int, string>
     */
    protected function withVersion(Collection $lines, string $version): Collection
    {
        $returnLine = (int)$lines->search(fn(string $line): bool => trim($line) === self::RETURN_LINE);
        $versionLines = ["    'config-version' => '{$version}',"];
        if (trim($lines->get($returnLine + 1) ?? '') !== '') {
            $versionLines[] = '';
        }

        $versionedLines = $lines->values();
        $versionedLines->splice($returnLine + 1, 0, $versionLines);

        return $versionedLines;
    }

    /**
     * @param string $diffFile
     *
     * @return list<list<array{string, string}>>
     */
    protected function hunks(string $diffFile): array
    {
        $hunks = [];
        $hunk = null;
        foreach (explode("\n", (new Filesystem())->readFile($diffFile)) as $line) {
            if (str_starts_with($line, '@@')) {
                if ($hunk !== null) {
                    $hunks[] = $hunk;
                }

                $hunk = [];

                continue;
            }

            // The file headers come before the first hunk.
            if ($hunk !== null && in_array(substr($line, 0, 1), [' ', '-', '+'], true)) {
                $hunk[] = [substr($line, 0, 1), substr($line, 1)];
            }
        }

        if ($hunk !== null) {
            $hunks[] = $hunk;
        }

        return $hunks;
    }

    /**
     * @param Collection<int, string> $lines
     * @param list<array{string, string}> $hunk
     *
     * @return array{Collection<int, string>, list<string>}|null
     */
    protected function applyHunk(Collection $lines, array $hunk): array|null
    {
        $oldLines = collect($hunk)
            ->reject(fn(array $line): bool => $line[0] === '+')
            ->map(fn(array $line): string => $line[1])
            ->values();
        $start = $this->locate($lines, $oldLines);
        if ($start === null) {
            return null;
        }

        $matchedLines = $lines->slice($start, $oldLines->count())->values()->all();
        $offset = 0;
        $newLines = [];
        $messages = [];
        // Each - line is paired with the + line in the same place, so a changed default keeps the project's value.
        $removedLines = [];
        $pairedCount = 0;
        foreach ($hunk as [$operation, $text]) {
            if ($operation === '-') {
                $removedLines[] = [$text, $matchedLines[$offset]];
                $offset++;

                continue;
            }

            if ($operation === ' ') {
                $messages = [...$messages, ...$this->removalMessages(collect($removedLines)->slice($pairedCount))];
                $removedLines = [];
                $pairedCount = 0;
                $newLines[] = $matchedLines[$offset];
                $offset++;

                continue;
            }

            $removedLine = $removedLines[$pairedCount] ?? null;
            if ($removedLine !== null) {
                $pairedCount++;
            }

            [$replacement, $replacementMessages] = $removedLine === null
                ? [[$text], $this->keyMessage('Added', $text)]
                : $this->replaceLine($removedLine[0], $removedLine[1], $text);
            $newLines = [...$newLines, ...$replacement];
            $messages = [...$messages, ...$replacementMessages];
        }

        $messages = [...$messages, ...$this->removalMessages(collect($removedLines)->slice($pairedCount))];
        $patchedLines = $lines->values();
        $patchedLines->splice($start, $offset, $newLines);

        return [$patchedLines, $messages];
    }

    /**
     * A match on the exact lines wins; failing that, key lines match on the key alone, so a changed value still matches.
     *
     * @param Collection<int, string> $lines
     * @param Collection<int, string> $oldLines
     *
     * @return int|null
     */
    protected function locate(Collection $lines, Collection $oldLines): int|null
    {
        if ($oldLines->isEmpty()) {
            return null;
        }

        $normalisers = [
            fn(string $line): string => trim($line),
            fn(string $line): string => $this->key($line) === null ? trim($line) : "'{$this->key($line)}' =>",
        ];
        if ($oldLines->count() > $lines->count()) {
            return null;
        }

        foreach ($normalisers as $normalise) {
            $needle = $oldLines->map($normalise)->all();
            $haystack = $lines->map($normalise);
            $matches = collect(range(0, $haystack->count() - count($needle)))
                ->filter(fn(int $start): bool => $haystack->slice($start, count($needle))->values()->all() === $needle);

            // A key such as rector sits under both format and check, so an ambiguous match is not applied.
            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        return null;
    }

    /**
     * @param string $oldDefault
     * @param string $publishedLine
     * @param string $newDefault
     *
     * @return array{list<string>, list<string>}
     */
    protected function replaceLine(string $oldDefault, string $publishedLine, string $newDefault): array
    {
        $oldKey = $this->key($oldDefault);
        $newKey = $this->key($newDefault);
        $isEdited = trim($publishedLine) !== trim($oldDefault);

        if ($oldKey !== null && $oldKey === $newKey) {
            // A config dated by its keys can already hold a later default.
            if (trim($publishedLine) === trim($newDefault)) {
                return [[$publishedLine], []];
            }

            if ($isEdited) {
                $newValue = $this->value($newDefault);

                return [[$publishedLine], ["Kept your value of {$newKey}, its default is now {$newValue}."]];
            }

            return [[$newDefault], ["Updated the default of {$newKey}."]];
        }

        return [[$newDefault], [...$this->removalMessages(collect([[$oldDefault, $publishedLine]])), ...$this->keyMessage('Added', $newDefault)]];
    }

    /**
     * @param Collection<int, array{string, string}> $removedLines
     *
     * @return Collection<int, non-falsy-string>
     */
    protected function removalMessages(Collection $removedLines): Collection
    {
        return $removedLines
            ->flatMap(function (array $removedLine): array {
                [$oldDefault, $publishedLine] = $removedLine;
                $key = $this->key($oldDefault);
                if ($key === null) {
                    return [];
                }

                // A diff can't tell a rename from a removal, so a value the project set is pointed out instead of carried over.
                return trim($publishedLine) === trim($oldDefault)
                    ? ["Removed {$key}."]
                    : ["Removed {$key}, move your value {$this->value($publishedLine)} over if it still applies."];
            })
            ->values();
    }

    /**
     * @param string $action
     * @param string $line
     *
     * @return list<string>
     */
    protected function keyMessage(string $action, string $line): array
    {
        $key = $this->key($line);

        return $key === null ? [] : ["{$action} {$key}."];
    }

    /**
     * @param string $line
     *
     * @return string|null
     */
    protected function key(string $line): string|null
    {
        // The key pattern needs at least one character, so an empty match means the line holds no key.
        $key = Str::match(self::KEY_PATTERN, $line);

        return $key === '' ? null : $key;
    }

    /**
     * @param string $line
     *
     * @return string|null
     */
    protected function value(string $line): string|null
    {
        if ($this->key($line) === null) {
            return null;
        }

        return rtrim(trim(Str::before(Str::match(self::VALUE_PATTERN, $line), ' //')), ',');
    }
}
