<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

final class PromotesConstructorProperties
{
    private string $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
}
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

final class PromotesConstructorProperties
{
    /**
     * @param string $name
     */
    public function __construct(private string $name)
    {
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
}
