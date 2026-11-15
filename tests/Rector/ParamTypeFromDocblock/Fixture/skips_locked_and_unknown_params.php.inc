<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ParamTypeFromDocblock\Fixture;

use ArrayAccess;

class SkipsLockedParent
{
    /**
     * @param mixed $value
     */
    public function store($value): void
    {
    }

    public function undocumented($value): void
    {
    }
}

class SkipsLockedChild extends SkipsLockedParent implements ArrayAccess
{
    /**
     * @param int $value
     */
    public function store($value): void
    {
    }

    /**
     * @param string $offset
     */
    public function offsetExists($offset): bool
    {
        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }

    /**
     * @param int $limit
     * @param int ...$ids
     */
    public function find($limit = null, ...$ids): void
    {
    }

    /**
     * @param resource $handle
     */
    public function close($handle): void
    {
    }
}

trait SkipsTraitMethods
{
    /**
     * @param int $value
     */
    public function set($value): void
    {
    }
}

?>
