<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RelationMethodReturnType\Fixture;

use Illuminate\Database\Eloquent\Model;

class SkipsNonRelations extends Model
{
    public function label()
    {
        return 'label';
    }

    public function scopedPart()
    {
        $relation = $this->belongsTo(SkipsNonRelations::class);

        return $relation->where('active', true);
    }

    public function typed(): \Illuminate\Database\Eloquent\Relations\Relation
    {
        return $this->belongsTo(SkipsNonRelations::class);
    }
}

class SkipsNonModels
{
    public function part()
    {
        return (new SkipsNonRelations())->belongsTo(SkipsNonRelations::class);
    }
}

?>
