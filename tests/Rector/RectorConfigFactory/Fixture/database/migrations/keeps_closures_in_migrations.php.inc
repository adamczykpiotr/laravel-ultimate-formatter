<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

final class KeepsClosuresInMigrations
{
    /**
     * @param list<int> $ids
     *
     * @return list<int>
     */
    public function up(array $ids): array
    {
        return array_values(array_filter($ids, function (int $id): bool {
            return $id > 0;
        }));
    }
}
