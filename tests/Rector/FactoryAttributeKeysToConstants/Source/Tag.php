<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    public const ATTRIBUTE_NAME = 'name';
    public const ATTRIBUTE_COLOR = 'color';
    public const RELATION_OWNER = 'owner';
}
