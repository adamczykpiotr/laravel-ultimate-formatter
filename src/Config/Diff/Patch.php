<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config\Diff;

use Illuminate\Support\Collection;

class Patch
{
    /**
     * @param Collection<int, string> $lines
     * @param list<string> $messages
     */
    public function __construct(
        public readonly Collection $lines,
        public readonly array $messages,
    ) {}
}
