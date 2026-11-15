<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

class ConfigMigration
{
    /**
     * @param ConfigMigrationOutcome $outcome
     * @param string $fromVersion
     * @param string $toVersion
     * @param list<string> $applied
     * @param list<string> $skipped
     */
    public function __construct(
        public readonly ConfigMigrationOutcome $outcome,
        public readonly string $fromVersion,
        public readonly string $toVersion,
        public readonly array $applied = [],
        public readonly array $skipped = [],
    ) {}

    /**
     * @return bool
     */
    public function isUpToDate(): bool
    {
        return $this->outcome === ConfigMigrationOutcome::UP_TO_DATE;
    }
}
