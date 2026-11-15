<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\GroupTraitUses\Fixture;

/**
 * @template TFactory
 */
trait HasFactoryWithDocblock
{
}

/**
 * @template TModel
 */
trait TracksRevisions
{
}

trait Notifiable
{
}

class KeepsUseDocblocks
{
    use Notifiable;

    /** @use HasFactoryWithDocblock<\stdClass> */
    use HasFactoryWithDocblock;

    /**
     * @use TracksRevisions<\ArrayObject>
     */
    use TracksRevisions;
}

class KeepsSingleUseDocblock
{
    use Notifiable;

    /** @use HasFactoryWithDocblock<\stdClass> */
    use HasFactoryWithDocblock;
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\GroupTraitUses\Fixture;

/**
 * @template TFactory
 */
trait HasFactoryWithDocblock
{
}

/**
 * @template TModel
 */
trait TracksRevisions
{
}

trait Notifiable
{
}

class KeepsUseDocblocks
{
    /**
     * @use HasFactoryWithDocblock<\stdClass>
     * @use TracksRevisions<\ArrayObject>
     */
    use HasFactoryWithDocblock, Notifiable, TracksRevisions;
}

class KeepsSingleUseDocblock
{
    /** @use HasFactoryWithDocblock<\stdClass> */
    use HasFactoryWithDocblock, Notifiable;
}

?>
