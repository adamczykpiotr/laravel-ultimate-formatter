<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'createdAt' => $this->created_at,
            'updated_at' => $this->updated_at,
            'owner' => [
                'displayName' => $this->owner_name,
                'avatar-url' => $this->owner_avatar,
            ],
            $this->mergeWhen(true, [
                'is_pinned' => true,
            ]),
            'posts' => $this->posts->map(fn($post): array => [
                'post_title' => $post->title,
            ]),
            'route' => route('tags.show', ['tag_id' => $this->id]),
        ];
    }
}
