<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->unsignedBigInteger(autoIncrement: false, column: 'editor_id');
            $table->string(length: 100, column: $this->column());
            $table->string(column: 'caption', length: 100);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['editor_id', 'caption']);
        });
    }

    protected function column(): string
    {
        return 'headline';
    }
};
