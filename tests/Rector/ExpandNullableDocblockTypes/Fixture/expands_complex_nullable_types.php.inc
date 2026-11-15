<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExpandNullableDocblockTypes\Fixture;

class ExpandsComplexNullableTypes
{
    /**
     * @param ?array<string, int> $a
     * @param ?Collection<int, User> $b
     * @param ?callable(int): void $c
     * @param ?int[] $d
     * @return ?array{a: int, b: string}
     */
    public function find($a, $b, $c, $d)
    {
        return null;
    }

    /**
     * @var ?array{
     *     id: int,
     *     name: string,
     * }
     */
    protected $row;

    /**
     * @param ?Foo|Bar $mixed
     * @param ? $broken
     */
    public function partlyExpanded($mixed, $broken): void
    {
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExpandNullableDocblockTypes\Fixture;

class ExpandsComplexNullableTypes
{
    /**
     * @param array<string, int>|null $a
     * @param Collection<int, User>|null $b
     * @param (callable(int): void)|null $c
     * @param int[]|null $d
     * @return array{a: int, b: string}|null
     */
    public function find($a, $b, $c, $d)
    {
        return null;
    }

    /**
     * @var array{
     *     id: int,
     *     name: string,
     * }|null
     */
    protected $row;

    /**
     * @param Foo|null|Bar $mixed
     * @param ? $broken
     */
    public function partlyExpanded($mixed, $broken): void
    {
    }
}

?>
