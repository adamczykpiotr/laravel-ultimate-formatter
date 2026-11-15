<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config\Diff;

enum DiffOperation
{
    case CONTEXT;
    case REMOVED;
    case ADDED;

    /**
     * @param string $prefix
     *
     * @return self|null
     */
    public static function fromPrefix(string $prefix): self|null
    {
        return collect(self::cases())->first(fn(self $operation): bool => $operation->prefix() === $prefix);
    }

    /**
     * @return string
     */
    public function prefix(): string
    {
        return match ($this) {
            self::CONTEXT => ' ',
            self::REMOVED => '-',
            self::ADDED => '+',
        };
    }
}
