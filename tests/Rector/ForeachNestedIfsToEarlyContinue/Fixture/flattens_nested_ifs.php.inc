<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ForeachNestedIfsToEarlyContinue\Fixture;

use DateTimeInterface;

class FlattensNestedIfs
{
    public function isValid(mixed $value): bool
    {
        return true;
    }

    /**
     * @param list<mixed> $values
     */
    public function run(array $values, int $limit): array
    {
        $valid = [];
        foreach ($values as $value) {
            if ($value !== null) {
                if ($this->isValid($value)) {
                    if ($value instanceof DateTimeInterface) {
                        if (!is_object($value) || $limit > 3) {
                            $valid[] = $value;
                            $limit--;
                        }
                    }
                }
            }
        }

        return $valid;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ForeachNestedIfsToEarlyContinue\Fixture;

use DateTimeInterface;

class FlattensNestedIfs
{
    public function isValid(mixed $value): bool
    {
        return true;
    }

    /**
     * @param list<mixed> $values
     */
    public function run(array $values, int $limit): array
    {
        $valid = [];
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            if ($this->isValid($value) === false) {
                continue;
            }
            if ($value instanceof DateTimeInterface === false) {
                continue;
            }
            if ((!is_object($value) || $limit > 3) === false) {
                continue;
            }
            $valid[] = $value;
            $limit--;
        }

        return $valid;
    }
}

?>
