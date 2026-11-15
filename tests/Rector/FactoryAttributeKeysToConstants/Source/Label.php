<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    public const ATTRIBUTE_TITLE = 'title';
    protected const ATTRIBUTE_SECRET = 'secret';
}
