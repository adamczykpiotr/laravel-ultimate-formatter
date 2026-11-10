<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\IdempotencyFixture;

use Illuminate\Support\Fluent;

class RewritesImportedUnionReturn
{
    /**
     * @param Fluent $attributes
     *
     * @return mixed
     */
    public function attributesOrFluent(Fluent $attributes): Fluent|array
    {
        return $attributes;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\IdempotencyFixture;

use Illuminate\Support\Fluent;

class RewritesImportedUnionReturn
{
    /**
     * @param Fluent $attributes
     *
     * @return mixed[]|Fluent
     */
    public function attributesOrFluent(Fluent $attributes): Fluent|array
    {
        return $attributes;
    }
}

?>
