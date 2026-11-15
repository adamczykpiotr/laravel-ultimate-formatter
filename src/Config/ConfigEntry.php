<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

/**
 * One 'key' => value element of a config array, by byte offsets. $endOffset follows the trailing comma when there is one.
 */
class ConfigEntry
{
    /**
     * @param int $keyOffset
     * @param int $keyLength
     * @param int $valueOffset
     * @param int $valueLength
     * @param int $endOffset
     * @param bool $hasComma
     * @param ConfigArray|null $children Null when the value is anything but a literal array.
     */
    public function __construct(
        public readonly int $keyOffset,
        public readonly int $keyLength,
        public readonly int $valueOffset,
        public readonly int $valueLength,
        public readonly int $endOffset,
        public readonly bool $hasComma,
        public readonly ConfigArray|null $children,
    ) {}
}
