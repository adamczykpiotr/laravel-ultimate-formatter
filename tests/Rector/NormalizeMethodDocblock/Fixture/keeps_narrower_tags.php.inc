<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class KeepsNarrowerTags
{
    /**
     * @param list<string> $names
     * @param non-empty-string $fallback
     * @return non-empty-string
     */
    public function first(array $names, string $fallback): string
    {
        return $names[0] ?? $fallback;
    }
}

?>
