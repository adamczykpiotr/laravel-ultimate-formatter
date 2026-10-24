<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

use stdClass;

class KeepsObjectShapeIntersections
{
    /**
     * @param object{id: int, area: float|null}&stdClass $row
     * @return int
     */
    public function id(stdClass $row): int
    {
        return $row->id;
    }
}

?>
