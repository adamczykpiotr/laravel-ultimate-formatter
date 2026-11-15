<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

use Illuminate\Database\Eloquent\Model;

class TypesDocumentedParamsAndRelations extends Model
{
    public const ATTRIBUTE_PART_ID = 'part_id';

    public function part() {
        return $this->belongsTo(TypesDocumentedParamsAndRelations::class, self::ATTRIBUTE_PART_ID);
    }

    /**
     * @param int $odometerKm
     * @return float
     */
    private function wearFactor($odometerKm) {
        return $odometerKm / 1000.0;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TypesDocumentedParamsAndRelations extends Model
{
    public const ATTRIBUTE_PART_ID = 'part_id';

    /**
     * @return BelongsTo<TypesDocumentedParamsAndRelations, $this>
     */
    public function part(): BelongsTo {
        return $this->belongsTo(TypesDocumentedParamsAndRelations::class, self::ATTRIBUTE_PART_ID);
    }

    /**
     * @param int $odometerKm
     * @return float
     */
    private function wearFactor(int $odometerKm): float {
        return $odometerKm / 1000.0;
    }
}

?>
