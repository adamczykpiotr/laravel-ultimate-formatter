<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

/**
 * What one run of the migrator did to a published config.
 */
class ConfigMigration
{
    /**
     * @param int $fromVersion
     * @param int $toVersion
     * @param list<string> $applied
     * @param list<string> $skipped
     */
    public function __construct(
        public readonly int $fromVersion,
        public readonly int $toVersion,
        public readonly array $applied = [],
        public readonly array $skipped = [],
    ) {}

    /**
     * @return bool
     */
    public function isUpToDate(): bool
    {
        return $this->fromVersion >= $this->toVersion;
    }
}
