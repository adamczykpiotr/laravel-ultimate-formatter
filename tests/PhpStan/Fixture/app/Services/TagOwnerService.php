<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;

final readonly class TagOwnerService
{
    public function __construct(
        private StatefulGuard $guard,
    ) {
    }

    public function claim(Authenticatable $owner): void
    {
        $this->guard->login($owner);
    }
}
