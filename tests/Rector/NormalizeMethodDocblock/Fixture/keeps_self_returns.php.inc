<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class KeepsSelfReturns
{
    /**
     * @return self|null
     */
    public static function make(): self|null
    {
        return null;
    }

    /**
     * @return static
     */
    public function copy(): static
    {
        return clone $this;
    }

    public function fresh(): self
    {
        return new self();
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class KeepsSelfReturns
{
    /**
     * @return self|null
     */
    public static function make(): self|null
    {
        return null;
    }

    /**
     * @return static
     */
    public function copy(): static
    {
        return clone $this;
    }

    /**
     * @return self
     */
    public function fresh(): self
    {
        return new self();
    }
}

?>
