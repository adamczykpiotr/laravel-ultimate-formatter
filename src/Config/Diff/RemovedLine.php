<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config\Diff;

class RemovedLine
{
    /**
     * @param string $oldDefault
     * @param string $publishedLine
     */
    public function __construct(
        public readonly string $oldDefault,
        public readonly string $publishedLine,
    ) {}

    /**
     * @return bool
     */
    public function isEdited(): bool
    {
        return trim($this->publishedLine) !== trim($this->oldDefault);
    }
}
