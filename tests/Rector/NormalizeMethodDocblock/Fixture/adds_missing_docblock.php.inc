<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class AddsMissingDocblock
{
    public function greet(string $name, int $times): string
    {
        return str_repeat($name, $times);
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class AddsMissingDocblock
{
    /**
     * @param string $name
     * @param int $times
     * @return string
     */
    public function greet(string $name, int $times): string
    {
        return str_repeat($name, $times);
    }
}

?>
