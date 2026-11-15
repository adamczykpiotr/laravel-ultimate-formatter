<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RenameGenericForeachValueVariable\Fixture;

class SkipsMeaningfulOrConflictingNames
{
    /**
     * @param list<string> $users
     * @param list<string> $vehicles
     * @param list<string> $drivers
     * @param list<string> $orders
     * @param list<string> $news
     */
    public function run(array $users, array $vehicles, string $vehicle, array $drivers, array $orders, array $news): array
    {
        $result = [];
        foreach ($users as $admin) {
            $result[] = $admin;
        }

        foreach ($vehicles as $item) {
            $result[] = $item . $vehicle;
        }

        foreach ($drivers as $row) {
            $result[] = $row;
        }
        $result[] = $row;

        foreach ($orders as $entry) {
            /** @var string $entry */
            $result[] = $entry;
        }

        foreach ($news as $value) {
            $result[] = $value;
        }

        return $result;
    }

    /**
     * @param list<string> $vehicles
     */
    public function dynamic(array $vehicles): array
    {
        foreach ($vehicles as $item) {
            $result = compact('item');
        }

        return $result ?? [];
    }
}

?>
