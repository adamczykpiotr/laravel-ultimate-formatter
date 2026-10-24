<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class SkipsConstructorReturn
{
    public function __construct(protected string $name)
    {
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class SkipsConstructorReturn
{
    /**
     * @param string $name
     */
    public function __construct(protected string $name)
    {
    }
}

?>
