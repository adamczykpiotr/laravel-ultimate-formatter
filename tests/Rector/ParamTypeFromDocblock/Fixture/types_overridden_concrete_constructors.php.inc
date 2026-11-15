<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ParamTypeFromDocblock\Fixture;

class ConcreteSeeded
{
    public function __construct($seed)
    {
    }
}

class TypesOverriddenConstructor extends ConcreteSeeded
{
    /**
     * @param int $seed
     */
    public function __construct($seed)
    {
        parent::__construct($seed);
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ParamTypeFromDocblock\Fixture;

class ConcreteSeeded
{
    public function __construct($seed)
    {
    }
}

class TypesOverriddenConstructor extends ConcreteSeeded
{
    /**
     * @param int $seed
     */
    public function __construct(int $seed)
    {
        parent::__construct($seed);
    }
}

?>
