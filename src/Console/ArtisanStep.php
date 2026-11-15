<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console;

enum ArtisanStep
{
    case CLEAR_CACHE;
    case MIGRATE;
    case IDE_HELPER;

    /**
     * @return string
     */
    public function configKey(): string
    {
        return match ($this) {
            self::CLEAR_CACHE => 'clear-cache',
            self::MIGRATE => 'migrate',
            self::IDE_HELPER => 'ide-helper',
        };
    }

    /**
     * ide-helper runs on --models alone, so it has no flag in the config's pre section.
     *
     * @return bool
     */
    public function hasPreFlag(): bool
    {
        return $this !== self::IDE_HELPER;
    }
}
