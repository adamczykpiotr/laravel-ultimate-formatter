<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RenameGenericForeachValueVariable\Fixture;

class RenamesGenericValueVariables
{
    /**
     * @var list<string>
     */
    protected array $drivers = [];

    /**
     * @param list<string> $vehicles
     * @param list<string> $tagNames
     */
    public function run(array $vehicles, array $tagNames): array
    {
        $parked = [];
        foreach ($vehicles as $index => $item) {
            $parked[$index] = $item;
            $callback = function () use ($item): string {
                return $item;
            };
        }

        foreach ($this->drivers as $item) {
            $parked[] = $item;
        }

        foreach ($tagNames as $value) {
            $parked[] = fn(): string => $value;
        }

        return $parked;
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RenameGenericForeachValueVariable\Fixture;

class RenamesGenericValueVariables
{
    /**
     * @var list<string>
     */
    protected array $drivers = [];

    /**
     * @param list<string> $vehicles
     * @param list<string> $tagNames
     */
    public function run(array $vehicles, array $tagNames): array
    {
        $parked = [];
        foreach ($vehicles as $index => $vehicle) {
            $parked[$index] = $vehicle;
            $callback = function () use ($vehicle): string {
                return $vehicle;
            };
        }

        foreach ($this->drivers as $driver) {
            $parked[] = $driver;
        }

        foreach ($tagNames as $tagName) {
            $parked[] = fn(): string => $tagName;
        }

        return $parked;
    }
}

?>
