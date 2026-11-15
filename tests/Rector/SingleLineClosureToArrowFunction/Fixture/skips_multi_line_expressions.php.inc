<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\SingleLineClosureToArrowFunction\Fixture;

class SkipsMultiLineExpressions
{
    public function chained(array $users): array
    {
        return array_map(function (array $user) {
            return collect($user['roles'])
                ->filter()
                ->values()
                ->all();
        }, $users);
    }

    public function multipleStatements(array $users): array
    {
        return array_map(function (array $user) {
            $name = $user['name'];

            return $name;
        }, $users);
    }
}
