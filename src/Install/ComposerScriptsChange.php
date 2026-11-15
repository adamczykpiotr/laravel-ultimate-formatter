<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

class ComposerScriptsChange
{
    /**
     * @param bool $hasComposerFile
     * @param array<int, string> $addedScripts
     * @param bool $addedMigrateConfig
     */
    public function __construct(
        public readonly bool $hasComposerFile,
        public readonly array $addedScripts = [],
        public readonly bool $addedMigrateConfig = false,
    ) {}

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->addedScripts === [] && $this->addedMigrateConfig === false;
    }
}
