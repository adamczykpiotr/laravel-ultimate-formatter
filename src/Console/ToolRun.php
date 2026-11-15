<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console;

class ToolRun
{
    /**
     * @param Tool $tool
     * @param array<int, string> $arguments
     */
    public function __construct(
        public readonly Tool $tool,
        public readonly array $arguments,
    ) {}
}
