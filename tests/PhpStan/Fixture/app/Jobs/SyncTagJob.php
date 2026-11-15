<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class SyncTagJob implements ShouldQueue
{
    protected int $attempt = 1;

    private string $source = 'api';

    public function __construct(
        protected int $tagId,
        private string $reason,
    ) {
    }
}
