<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\SingleLineClosureToArrowFunction\Fixture;

class ConvertsSingleLineClosures
{
    public function run(array $users, int $offset): array
    {
        $names = array_map(function (array $user) {
            return $user['name'];
        }, $users);

        return array_map(static function (int $id) use ($offset): int {
            return $id + $offset;
        }, $names);
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\SingleLineClosureToArrowFunction\Fixture;

class ConvertsSingleLineClosures
{
    public function run(array $users, int $offset): array
    {
        $names = array_map(fn(array $user) => $user['name'], $users);

        return array_map(static fn(int $id): int => $id + $offset, $names);
    }
}

?>
