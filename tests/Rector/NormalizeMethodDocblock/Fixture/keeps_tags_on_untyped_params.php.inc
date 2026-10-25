<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class KeepsTagsOnUntypedParams
{
    /**
     * @param \DateTimeInterface $date
     * @return string
     */
    public function format($date, $pattern): string
    {
        return $date->format($pattern);
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class KeepsTagsOnUntypedParams
{
    /**
     * @param \DateTimeInterface $date
     * @return string
     * @param mixed $pattern
     */
    public function format($date, $pattern): string
    {
        return $date->format($pattern);
    }
}

?>
