<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\IdempotencyFixture;

use Illuminate\Support\Fluent;

class ReordersImportedUnionParam
{
    /**
     * @param Fluent $current
     * @param Fluent|mixed[] $candidate
     * @param string[] $extraIgnore
     *
     * @return bool
     */
    public function contentChanged(Fluent $current, Fluent|array $candidate, array $extraIgnore = []): bool
    {
        return $current !== $candidate;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\IdempotencyFixture;

use Illuminate\Support\Fluent;

class ReordersImportedUnionParam
{
    /**
     * @param Fluent $current
     * @param mixed[]|Fluent $candidate
     * @param string[] $extraIgnore
     *
     * @return bool
     */
    public function contentChanged(Fluent $current, Fluent|array $candidate, array $extraIgnore = []): bool
    {
        return $current !== $candidate;
    }
}

?>
