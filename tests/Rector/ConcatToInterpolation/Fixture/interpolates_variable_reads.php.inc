<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ConcatToInterpolation\Fixture;

class InterpolatesVariableReads
{
    public string $name = 'meter';

    /**
     * @param array<string, string> $row
     */
    public function run(int $count, array $row, ?self $other): array
    {
        $message = 'Imported ' . $count . ' readings for ' . $this->name;
        $key = $row['city'] . '|' . $row['zip'];
        $nullsafe = 'Other: ' . $other?->name;
        $escaped = 'Cost: $' . $count . " \"total\"\n";
        $merged = "Hello {$count}" . ' and ' . $this->name;
        $message .= ' for ' . $count;

        return [$message, $key, $nullsafe, $escaped, $merged];
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ConcatToInterpolation\Fixture;

class InterpolatesVariableReads
{
    public string $name = 'meter';

    /**
     * @param array<string, string> $row
     */
    public function run(int $count, array $row, ?self $other): array
    {
        $message = "Imported {$count} readings for {$this->name}";
        $key = "{$row['city']}|{$row['zip']}";
        $nullsafe = "Other: {$other?->name}";
        $escaped = "Cost: \${$count} \"total\"\n";
        $merged = "Hello {$count} and {$this->name}";
        $message .= " for {$count}";

        return [$message, $key, $nullsafe, $escaped, $merged];
    }
}

?>
