<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Install;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use AdamczykPiotr\UltimateFormatter\Console\ProjectLayout;

enum ProjectFile
{
    case PHP_CS_FIXER_CONFIG;
    case RECTOR_CONFIG;
    case PHPSTAN_CONFIG;
    case GIT_BLAME_IGNORE_REVS;
    case FORMATTER_CONFIG;
    case IDE_HELPER_CONFIG;
    case CI_WORKFLOW;

    /**
     * @return string
     */
    public function path(): string
    {
        return match ($this) {
            self::PHP_CS_FIXER_CONFIG => '.php-cs-fixer.php',
            self::RECTOR_CONFIG => 'rector.php',
            self::PHPSTAN_CONFIG => 'phpstan.neon',
            self::GIT_BLAME_IGNORE_REVS => '.git-blame-ignore-revs',
            self::FORMATTER_CONFIG => ConfigReader::PUBLISHED_CONFIG_FILE,
            self::IDE_HELPER_CONFIG => 'config/ide-helper.php',
            self::CI_WORKFLOW => '.github/workflows/code-style.yml',
        };
    }

    /**
     * The blame ignore file holds recorded commit SHAs, so --force never overwrites it.
     *
     * @return bool
     */
    public function isOverwritable(): bool
    {
        return $this !== self::GIT_BLAME_IGNORE_REVS;
    }

    /**
     * A package's config/ directory holds the configs it ships, so only an app gets the formatter and IDE helper configs.
     *
     * @param ProjectLayout $layout
     * @param bool $ci
     *
     * @return bool
     */
    public function isInstalledFor(ProjectLayout $layout, bool $ci): bool
    {
        return match ($this) {
            self::FORMATTER_CONFIG, self::IDE_HELPER_CONFIG => $layout === ProjectLayout::APP,
            self::CI_WORKFLOW => $ci,
            default => true,
        };
    }
}
