<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class TagService
{
    public function create(Request $request): Tag
    {
        $name = sprintf('%s', request()->string('name'));
        $tags = array_map(fn(string $tag): string => $tag, [$name]);
        $exists = method_exists(Tag::class, 'scopeNamed') || file_exists('tags.json') || preg_match('/a/', $name) === 1;
        auth()->id();
        session()->get('tag');
        env('TAG_LIMIT');
        redirect('/tags');
        Session::get('tag');

        $tag = Tag::create(['name' => $name]);
        Tag::firstOrCreate(['name' => $name]);
        Tag::updateOrCreate(['name' => $name]);
        Tag::query()->create(['name' => $name]);
        $tag->fill(['name' => $name]);
        $tag->update(['name' => $name]);
        $tag->refresh();

        if (app()->environment('local') || $exists || $tags === []) {
            return $tag;
        }

        return $tag;
    }

    public function fetch(): Response
    {
        return Http::get('https://example.com/tags');
    }
}
