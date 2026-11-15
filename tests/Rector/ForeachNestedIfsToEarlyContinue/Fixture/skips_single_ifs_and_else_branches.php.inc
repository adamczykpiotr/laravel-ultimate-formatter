<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ForeachNestedIfsToEarlyContinue\Fixture;

class SkipsSingleIfsAndElseBranches
{
    /**
     * @param list<int> $values
     */
    public function run(array $values): array
    {
        $kept = [];
        foreach ($values as $value) {
            if ($value > 1) {
                $kept[] = $value;
            }
        }

        foreach ($values as $value) {
            if ($value > 1) {
                if ($value < 10) {
                    $kept[] = $value;
                }
            } else {
                $kept[] = 0;
            }
        }

        foreach ($values as $value) {
            $kept[] = -1;
            if ($value > 1) {
                if ($value < 10) {
                    $kept[] = $value;
                }
            }
        }

        return $kept;
    }
}

?>
