<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExpandNullableDocblockTypes\Fixture;

class SkipsExplicitUnions
{
    /**
     * @param string|null $name
     * @return int|null
     */
    public function find(?string $name): ?int
    {
        return null;
    }
}

?>
