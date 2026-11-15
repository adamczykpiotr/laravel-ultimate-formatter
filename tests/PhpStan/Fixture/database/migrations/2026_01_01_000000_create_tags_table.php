<?php

declare(strict_types=1);

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Tag::query()->delete();
    }
};
