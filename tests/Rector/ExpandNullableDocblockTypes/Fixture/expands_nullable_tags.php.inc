<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExpandNullableDocblockTypes\Fixture;

class ExpandsNullableTags
{
    /**
     * @var ?string
     */
    protected $label;

    /**
     * @param ?string $name
     * @return ?int
     */
    public function find(?string $name): ?int
    {
        return null;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExpandNullableDocblockTypes\Fixture;

class ExpandsNullableTags
{
    /**
     * @var string|null
     */
    protected $label;

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
