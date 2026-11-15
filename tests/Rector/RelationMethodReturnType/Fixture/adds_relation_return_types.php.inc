<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RelationMethodReturnType\Fixture;

use Illuminate\Database\Eloquent\Model;

class AddsRelationReturnTypes extends Model
{
    public const ATTRIBUTE_PART_ID = 'part_id';

    public function part()
    {
        return $this->belongsTo(AddsRelationReturnTypes::class, self::ATTRIBUTE_PART_ID);
    }

    public function children()
    {
        return $this->hasMany(AddsRelationReturnTypes::class);
    }

    public function tags()
    {
        return $this->belongsToMany(AddsRelationReturnTypes::class)->withTimestamps();
    }

    public function owner()
    {
        return $this->morphTo();
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RelationMethodReturnType\Fixture;

use Illuminate\Database\Eloquent\Model;

class AddsRelationReturnTypes extends Model
{
    public const ATTRIBUTE_PART_ID = 'part_id';

    public function part(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AddsRelationReturnTypes::class, self::ATTRIBUTE_PART_ID);
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AddsRelationReturnTypes::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(AddsRelationReturnTypes::class)->withTimestamps();
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}

?>
