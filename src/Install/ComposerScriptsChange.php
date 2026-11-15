<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

class ComposerScriptsChange
{
    /**
     * @param array<int, string> $addedScripts
     * @param bool $addedMigrateConfig
     */
    public function __construct(
        public readonly array $addedScripts,
        public readonly bool $addedMigrateConfig,
    ) {}

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->addedScripts === [] && $this->addedMigrateConfig === false;
    }
}
