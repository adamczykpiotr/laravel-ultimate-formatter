<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ParamTypeFromDocblock\Fixture;

use Illuminate\Support\Collection;

class AddsDocumentedParamTypes
{
    /**
     * @param int $odometerKm
     * @return float
     */
    private function wearFactor($odometerKm)
    {
        return $odometerKm / 1000;
    }

    /**
     * @param string|null $name
     * @param list<string> $tags
     * @param Collection<int, string> $items
     * @param int|string $key
     * @param bool $strict
     */
    public function handle($name, $tags, $items, $key, $strict = false): void
    {
    }

    /**
     * @param int|null $limit
     */
    public function paginate($limit = null): void
    {
    }
}

/**
 * @param positive-int $count
 */
function repeatTimes($count): void
{
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ParamTypeFromDocblock\Fixture;

use Illuminate\Support\Collection;

class AddsDocumentedParamTypes
{
    /**
     * @param int $odometerKm
     * @return float
     */
    private function wearFactor(int $odometerKm)
    {
        return $odometerKm / 1000;
    }

    /**
     * @param string|null $name
     * @param list<string> $tags
     * @param Collection<int, string> $items
     * @param int|string $key
     * @param bool $strict
     */
    public function handle(?string $name, array $tags, \Illuminate\Support\Collection $items, int|string $key, bool $strict = false): void
    {
    }

    /**
     * @param int|null $limit
     */
    public function paginate(?int $limit = null): void
    {
    }
}

/**
 * @param positive-int $count
 */
function repeatTimes(int $count): void
{
}

?>
