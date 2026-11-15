<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExplicitNullFalseComparison\Fixture;

class SkipsAmbiguousTruthiness
{
    /**
     * @param array<int, string> $tags
     */
    public function run(string $name, int $count, array $tags, ?bool $flag, ?string $label, $untyped): bool
    {
        return !$name || !$count || !$tags || !$flag || !$label || !$untyped;
    }
}

?>
