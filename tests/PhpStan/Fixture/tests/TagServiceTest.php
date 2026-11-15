<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TagServiceTest
{
    public function testLogs(): void
    {
        Log::shouldReceive('info');
        Log::spy();
        Cache::shouldReceive('get');
        auth()->id();
        App::environment('local');
    }
}
