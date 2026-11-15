<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExplicitNullFalseComparison\Fixture;

use DateTimeImmutable;

class ComparesNullableObjectsAndBools
{
    public bool $isAdmin = false;

    public function isLocked(): bool
    {
        return false;
    }

    public function run(?DateTimeImmutable $deletedAt, bool $force): bool
    {
        if (!$deletedAt) {
            return false;
        }

        if (!$this->isAdmin || !$this->isLocked()) {
            return false;
        }

        return !$force;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ExplicitNullFalseComparison\Fixture;

use DateTimeImmutable;

class ComparesNullableObjectsAndBools
{
    public bool $isAdmin = false;

    public function isLocked(): bool
    {
        return false;
    }

    public function run(?DateTimeImmutable $deletedAt, bool $force): bool
    {
        if ($deletedAt === null) {
            return false;
        }

        if ($this->isAdmin === false || $this->isLocked() === false) {
            return false;
        }

        return $force === false;
    }
}

?>
