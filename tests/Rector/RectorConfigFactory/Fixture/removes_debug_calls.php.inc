<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

class RemovesDebugCalls
{
    /**
     * @param array<string, int> $totals
     *
     * @return int
     */
    public function sum(array $totals): int
    {
        dd($totals);
        dump($totals);
        var_dump($totals);
        print_r($totals);
        ray($totals);

        return 0;
    }
}
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

class RemovesDebugCalls
{
    /**
     * @param array<string, int> $totals
     *
     * @return int
     */
    public function sum(array $totals): int
    {
        return 0;
    }
}
