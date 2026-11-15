<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected const STATUSES = ['DRAFT', 'PUBLISHED'];

    protected const DESCRIPTION = 'Blog posts';

    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('title', 100)->nullable();
            $table->enum('status', self::STATUSES);
            $table->unsignedBigInteger('views');
            $table->morphs('postable');
            $table->timestamps();
            $table->index(['title', 'status']);
            $table->unique('title');
            $table->comment(self::DESCRIPTION);
        });

        Schema::table('tags', function (Blueprint $table): void {
            $table->string('color')->after('name');
            $table->dropColumn(['icon', 'label']);
            $table->dropMorphs('taggable');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->dropColumn('color');
        });

        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::dropIfExists('posts');
        });
    }
};
