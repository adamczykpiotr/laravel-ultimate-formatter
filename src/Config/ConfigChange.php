<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

/**
 * One change to the keys of config/ultimate-formatter.php. A path lists the array keys from the top level down,
 * since rule identifiers such as larastan.noEnvCallsOutsideOfConfig contain dots themselves.
 */
class ConfigChange
{
    /**
     * @param ConfigChangeType $type
     * @param non-empty-list<string> $path
     * @param mixed $value
     * @param string|null $newKey
     * @param string|null $after
     */
    public function __construct(
        public readonly ConfigChangeType $type,
        public readonly array $path,
        public readonly mixed $value = null,
        public readonly string|null $newKey = null,
        public readonly string|null $after = null,
    ) {}

    /**
     * Inserts the key with its default value, after the $after sibling when it exists, otherwise at the end of its array.
     *
     * @param non-empty-list<string> $path
     * @param mixed $value
     * @param string|null $after
     *
     * @return self
     */
    public static function added(array $path, mixed $value, string|null $after = null): self
    {
        return new self(ConfigChangeType::ADDED, $path, value: $value, after: $after);
    }

    /**
     * @param non-empty-list<string> $path
     *
     * @return self
     */
    public static function removed(array $path): self
    {
        return new self(ConfigChangeType::REMOVED, $path);
    }

    /**
     * Renames the last key of the path within the same array, keeping the project's value.
     *
     * @param non-empty-list<string> $path
     * @param string $newKey
     *
     * @return self
     */
    public static function renamed(array $path, string $newKey): self
    {
        return new self(ConfigChangeType::RENAMED, $path, newKey: $newKey);
    }

    /**
     * @return list<string>
     */
    public function parentPath(): array
    {
        return array_slice($this->path, 0, -1);
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->path[array_key_last($this->path)];
    }

    /**
     * @return non-empty-list<string>
     */
    public function newPath(): array
    {
        return [...$this->parentPath(), (string)$this->newKey];
    }
}
