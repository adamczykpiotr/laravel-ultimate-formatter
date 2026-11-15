<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected const TABLE = 'posts';

    protected array $columns = ['summary', 'body'];

    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->foreignId('author_id')->constrained($this->authors());
            $table->timestamps();
            $table->text('body');
            $table->softDeletes();
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn($this->columns);
        });

        foreach (['summary', 'body'] as $column) {
            Schema::table('posts', function (Blueprint $table) use ($column): void {
                $table->text($column);
            });
        }

        Schema::table('posts', fn(Blueprint $table) => $table->string('slug'));

        Schema::table('posts', function ($table) {
            $table->string('subtitle');
        });

        Schema::connection('reporting')->table('posts', function (Blueprint $table): void {
            $table->string('excerpt');
        });
    }

    protected function authors(): string
    {
        return 'users';
    }
};
