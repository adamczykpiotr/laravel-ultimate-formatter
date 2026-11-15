<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ParamTypeFromDocblock\Fixture;

class SkipsPromotedParams
{
    /**
     * @param int $count
     * @param callable $callback
     */
    public function __construct(public $count, protected $callback)
    {
    }
}

interface MakesFromSeed
{
    public function __construct($seed);
}

class SkipsInterfaceConstructor implements MakesFromSeed
{
    /**
     * @param int $seed
     */
    public function __construct($seed)
    {
    }
}

abstract class AbstractSeeded
{
    abstract public function __construct($seed);
}

class SkipsAbstractConstructor extends AbstractSeeded
{
    /**
     * @param int $seed
     */
    public function __construct($seed)
    {
    }
}

trait RequiresSeed
{
    abstract public function __construct($seed);
}

class SkipsTraitAbstractConstructor
{
    use RequiresSeed;

    /**
     * @param int $seed
     */
    public function __construct($seed)
    {
    }
}

?>
