<?php

declare(strict_types=1);

namespace App\Services;

final readonly class TagCache
{
    public function __construct(
        private string $prefix,
    ) {
    }
}
