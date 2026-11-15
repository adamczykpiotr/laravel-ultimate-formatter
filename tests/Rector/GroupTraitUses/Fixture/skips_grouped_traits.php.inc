<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\GroupTraitUses\Fixture;

trait Sortable
{
}

trait Searchable
{
}

class SkipsGroupedTraits
{
    use Searchable, Sortable;

    public const ATTRIBUTE_NAME = 'name';
}

?>
