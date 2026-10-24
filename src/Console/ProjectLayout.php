<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console;

enum ProjectLayout: string
{
    case APP = 'APP';
    case PACKAGE = 'PACKAGE';

    /**
     * @param string $path
     * @return self|null
     */
    public static function detect(string $path): self|null
    {
        if (is_file("{$path}/artisan") && is_dir("{$path}/app")) {
            return self::APP;
        }

        if (is_dir("{$path}/src")) {
            return self::PACKAGE;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function fixerDirectories(): array
    {
        return match ($this) {
            self::APP => ['app', 'tests'],
            self::PACKAGE => ['src', 'tests'],
        };
    }

    /**
     * @return list<string>
     */
    public function rectorDirectories(): array
    {
        return match ($this) {
            self::APP => ['app', 'database', 'tests'],
            self::PACKAGE => ['src', 'tests'],
        };
    }

    /**
     * @return list<string>
     */
    public function rectorSkippedDirectories(): array
    {
        return match ($this) {
            self::APP => ['database/migrations'],
            self::PACKAGE => [],
        };
    }

    /**
     * @return string
     */
    public function fixerCacheFile(): string
    {
        return match ($this) {
            self::APP => 'storage/framework/cache/.php-cs-fixer.cache',
            self::PACKAGE => 'build/.php-cs-fixer.cache',
        };
    }
}
