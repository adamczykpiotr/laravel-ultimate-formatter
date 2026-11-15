<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

/**
 * A literal [...] array in a config file, by the byte offsets of its brackets.
 */
class ConfigArray
{
    /**
     * @param int $openOffset
     * @param int $closeOffset
     * @param array<string, ConfigEntry> $entries
     */
    public function __construct(
        public readonly int $openOffset,
        public readonly int $closeOffset,
        public readonly array $entries,
    ) {}

    /**
     * @return ConfigEntry|null
     */
    public function lastEntry(): ConfigEntry|null
    {
        $lastKey = array_key_last($this->entries);

        return $lastKey === null ? null : $this->entries[$lastKey];
    }
}
