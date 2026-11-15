<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

class RenderedFile
{
    /**
     * @param ProjectFile $file
     * @param string $contents
     */
    public function __construct(
        public readonly ProjectFile $file,
        public readonly string $contents,
    ) {}
}
