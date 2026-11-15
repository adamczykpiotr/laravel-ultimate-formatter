<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ConcatToInterpolation\Fixture;

class SkipsExpressionsAndMultilineChains
{
    public const PREFIX = 'tag';

    public function run(int $count, string $name): array
    {
        $call = 'Name: ' . $name . ' (' . strtoupper($name) . ')';
        $constant = self::PREFIX . '.' . $name;
        $math = 'Total: ' . ($count + 1);
        $multiline = 'Imported ' . $count
            . ' readings for ' . $name;

        return [$call, $constant, $math, $multiline];
    }
}

?>
