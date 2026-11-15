<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

// PRIVATIZATION would make the property private, which breaks queue serialization,
// and CODING_STYLE would turn the interpolation into sprintf().
final class KeepsJobPropertiesAndInterpolation
{
    protected int $tagId = 0;

    /**
     * @return string
     */
    public function handle(): string
    {
        return "Exporting tag {$this->tagId}";
    }
}
