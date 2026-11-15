<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\GroupTraitUses\Fixture;

trait Greets
{
    public function hello(): string
    {
        return 'greets';
    }
}

trait Waves
{
    public function hello(): string
    {
        return 'waves';
    }
}

class MergesAdaptations
{
    use Waves;
    use Greets {
        Greets::hello insteadof Waves;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\GroupTraitUses\Fixture;

trait Greets
{
    public function hello(): string
    {
        return 'greets';
    }
}

trait Waves
{
    public function hello(): string
    {
        return 'waves';
    }
}

class MergesAdaptations
{
    use Greets, Waves {
        Greets::hello insteadof Waves;
    }
}

?>
