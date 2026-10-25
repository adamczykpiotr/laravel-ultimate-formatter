<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class RewritesStaleReturn
{
    /**
     * @param int $value
     * @return mixed
     */
    public function isPositive(int $value): bool
    {
        return $value > 0;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class RewritesStaleReturn
{
    /**
     * @param int $value
     * @return bool
     */
    public function isPositive(int $value): bool
    {
        return $value > 0;
    }
}

?>
