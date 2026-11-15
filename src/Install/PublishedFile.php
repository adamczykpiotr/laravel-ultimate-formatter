<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

class PublishedFile
{
    /**
     * @param string $path The file the project ends up with: the one written, or the existing one that was kept.
     * @param bool $isWritten
     */
    public function __construct(
        public readonly string $path,
        public readonly bool $isWritten,
    ) {}
}
