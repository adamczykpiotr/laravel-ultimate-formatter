<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\GroupTraitUses\Fixture;

trait SoftDeletes
{
}

trait HasFactory
{
}

trait Auditable
{
}

class GroupsAndSortsTraits
{
    public const ATTRIBUTE_NAME = 'name';

    use SoftDeletes;
    use HasFactory;

    protected string $table = 'tags';

    use Auditable, HasFactory;
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\GroupTraitUses\Fixture;

trait SoftDeletes
{
}

trait HasFactory
{
}

trait Auditable
{
}

class GroupsAndSortsTraits
{
    use Auditable, HasFactory, SoftDeletes;
    public const ATTRIBUTE_NAME = 'name';

    protected string $table = 'tags';
}

?>
