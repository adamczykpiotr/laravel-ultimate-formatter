<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Console;

enum RunMode
{
    case FORMAT;
    case CHECK;

    /**
     * @return string
     */
    public function configKey(): string
    {
        return match ($this) {
            self::FORMAT => 'format',
            self::CHECK => 'check',
        };
    }
}
