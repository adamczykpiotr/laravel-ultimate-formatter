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
    protected const KEY_PATTERN = "/^\\s*'([^']+)'\\s*=>(.*)$/";
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
        $lines = explode("\n", $filesystem->readFile($configFile));
        $isVersioned = $this->publishedVersion($lines) !== null;
        $fromVersion = $this->publishedVersion($lines) ?? $this->unversionedVersion($lines);
        $toVersion = $this->latestVersion();
        if ($isVersioned && version_compare($fromVersion, $toVersion, '>=')) {
            return new ConfigMigration($fromVersion, $fromVersion);
        }

        // The diffs leave the version line out, so it is lifted out while they apply and written back after.
        $lines = array_values(collect($lines)->reject(fn(string $line): bool => preg_match(self::VERSION_PATTERN, $line) === 1)->all());
        $returnLine = collect($lines)->search(fn(string $line): bool => trim($line) === self::RETURN_LINE);
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
            $filesystem->dumpFile($configFile, implode("\n", $lines));
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
     * @param list<string> $lines
     *
     * @return string|null
     */
    protected function publishedVersion(array $lines): string|null
    {
        $versionLine = collect($lines)->first(fn(string $line): bool => preg_match(self::VERSION_PATTERN, $line) === 1);

        return is_string($versionLine) ? Str::match(self::VERSION_PATTERN, $versionLine) : null;
    }

    /**
     * A config published before config-version existed is dated by its keys: the newest release whose added keys are
     * all there, so every later diff still applies to it.
     *
     * @param list<string> $lines
     *
     * @return string
     */
    protected function unversionedVersion(array $lines): string
    {
        $publishedKeys = collect($lines)->map(fn(string $line): string|null => $this->key($line))->filter()->values();

        return $this->versions()
            ->filter(function (string $version) use ($publishedKeys): bool {
                $addedKeys = $this->addedKeys("{$this->diffsDirectory}/{$version}.diff");

                return $addedKeys !== [] && collect($addedKeys)->diff($publishedKeys)->isEmpty();
            })
            ->last() ?? self::INITIAL_VERSION;
    }

    /**
     * @param string $diffFile
     *
     * @return list<string>
     */
    protected function addedKeys(string $diffFile): array
    {
        $lines = collect($this->hunks($diffFile))->flatMap(fn(array $hunk): array => $hunk);
        $keysOf = fn(string $operation): Collection => $lines
            ->filter(fn(array $line): bool => $line[0] === $operation)
            ->map(fn(array $line): string|null => $this->key($line[1]))
            ->filter();

        return array_values($keysOf('+')->diff($keysOf('-'))->unique()->all());
    }

    /**
     * @param list<string> $lines
     * @param string $version
     *
     * @return list<string>
     */
    protected function withVersion(array $lines, string $version): array
    {
        $returnLine = (int)collect($lines)->search(fn(string $line): bool => trim($line) === self::RETURN_LINE);
        $versionLines = ["    'config-version' => '{$version}',"];
        if (trim($lines[$returnLine + 1] ?? '') !== '') {
            $versionLines[] = '';
        }

        return [...array_slice($lines, 0, $returnLine + 1), ...$versionLines, ...array_slice($lines, $returnLine + 1)];
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
     * @param list<string> $lines
     * @param list<array{string, string}> $hunk
     *
     * @return array{list<string>, list<string>}|null
     */
    protected function applyHunk(array $lines, array $hunk): array|null
    {
        $oldLines = array_values(collect($hunk)
            ->reject(fn(array $line): bool => $line[0] === '+')
            ->map(fn(array $line): string => $line[1])
            ->all());
        $start = $this->locate($lines, $oldLines);
        if ($start === null) {
            return null;
        }

        $position = $start;
        $newLines = [];
        $messages = [];
        // Each - line is paired with the + line in the same place, so a changed default keeps the project's value.
        $removedLines = [];
        foreach ($hunk as [$operation, $text]) {
            if ($operation === '-') {
                $removedLines[] = [$text, $lines[$position]];
                $position++;

                continue;
            }

            if ($operation === ' ') {
                $messages = [...$messages, ...$this->removalMessages($removedLines)];
                $removedLines = [];
                $newLines[] = $lines[$position];
                $position++;

                continue;
            }

            $removedLine = array_shift($removedLines);
            [$replacement, $replacementMessages] = $removedLine === null
                ? [[$text], $this->keyMessage('Added', $text)]
                : $this->replaceLine($removedLine[0], $removedLine[1], $text);
            $newLines = [...$newLines, ...$replacement];
            $messages = [...$messages, ...$replacementMessages];
        }

        $messages = [...$messages, ...$this->removalMessages($removedLines)];
        $lines = [...array_slice($lines, 0, $start), ...$newLines, ...array_slice($lines, $position)];

        return [$lines, $messages];
    }

    /**
     * A match on the exact lines wins; failing that, key lines match on the key alone, so a changed value still matches.
     *
     * @param list<string> $lines
     * @param list<string> $oldLines
     *
     * @return int|null
     */
    protected function locate(array $lines, array $oldLines): int|null
    {
        if ($oldLines === []) {
            return null;
        }

        $normalisers = [
            fn(string $line): string => trim($line),
            fn(string $line): string => $this->key($line) === null ? trim($line) : "'{$this->key($line)}' =>",
        ];
        if (count($oldLines) > count($lines)) {
            return null;
        }

        foreach ($normalisers as $normalise) {
            $needle = collect($oldLines)->map($normalise)->all();
            $haystack = collect($lines)->map($normalise)->all();
            $matches = collect(range(0, count($haystack) - count($needle)))
                ->filter(fn(int $start): bool => array_slice($haystack, $start, count($needle)) === $needle);

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

        return [[$newDefault], [...$this->removalMessages([[$oldDefault, $publishedLine]]), ...$this->keyMessage('Added', $newDefault)]];
    }

    /**
     * @param list<array{string, string}> $removedLines
     *
     * @return list<string>
     */
    protected function removalMessages(array $removedLines): array
    {
        return array_values(collect($removedLines)
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
            ->all());
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
        return preg_match(self::KEY_PATTERN, $line, $matches) === 1 ? $matches[1] : null;
    }

    /**
     * @param string $line
     *
     * @return string|null
     */
    protected function value(string $line): string|null
    {
        if (preg_match(self::KEY_PATTERN, $line, $matches) !== 1) {
            return null;
        }

        return rtrim(trim(Str::before($matches[2], ' //')), ',');
    }
}
