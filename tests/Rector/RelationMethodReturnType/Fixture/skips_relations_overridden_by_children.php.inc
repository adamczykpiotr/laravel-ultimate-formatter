<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RelationMethodReturnType\Fixture;

use Illuminate\Database\Eloquent\Model;

class OverriddenUser extends Model
{
    public function avatar()
    {
        return $this->hasOne(OverriddenUser::class);
    }

    public function team()
    {
        return $this->belongsTo(OverriddenUser::class);
    }

    public function manager()
    {
        return $this->belongsTo(OverriddenUser::class);
    }
}

class OverriddenAdmin extends OverriddenUser
{
    public function avatar()
    {
        return $this->morphOne(OverriddenUser::class, 'imageable');
    }

    public function team()
    {
        $relation = $this->belongsTo(OverriddenUser::class);

        return $relation;
    }
}

class OverriddenSuperAdmin extends OverriddenAdmin
{
    public function manager()
    {
        $relation = $this->belongsTo(OverriddenUser::class);

        return $relation;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RelationMethodReturnType\Fixture;

use Illuminate\Database\Eloquent\Model;

class OverriddenUser extends Model
{
    public function avatar()
    {
        return $this->hasOne(OverriddenUser::class);
    }

    public function team()
    {
        return $this->belongsTo(OverriddenUser::class);
    }

    public function manager()
    {
        return $this->belongsTo(OverriddenUser::class);
    }
}

class OverriddenAdmin extends OverriddenUser
{
    public function avatar(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(OverriddenUser::class, 'imageable');
    }

    public function team()
    {
        $relation = $this->belongsTo(OverriddenUser::class);

        return $relation;
    }
}

class OverriddenSuperAdmin extends OverriddenAdmin
{
    public function manager()
    {
        $relation = $this->belongsTo(OverriddenUser::class);

        return $relation;
    }
}

?>
