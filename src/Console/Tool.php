<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console;

use LogicException;

enum Tool
{
    case ARTISAN;
    case PHP_CS_FIXER;
    case PHPSTAN;
    case RECTOR;

    /**
     * @return string
     */
    public function script(): string
    {
        return match ($this) {
            self::ARTISAN => 'artisan',
            self::PHP_CS_FIXER => 'vendor/bin/php-cs-fixer',
            self::PHPSTAN => 'vendor/bin/phpstan',
            self::RECTOR => 'vendor/bin/rector',
        };
    }

    /**
     * @return string
     */
    public function configKey(): string
    {
        return match ($this) {
            self::ARTISAN => throw new LogicException('Artisan arguments are configured per ArtisanStep.'),
            self::PHP_CS_FIXER => 'php-cs-fixer',
            self::PHPSTAN => 'phpstan',
            self::RECTOR => 'rector',
        };
    }
}
