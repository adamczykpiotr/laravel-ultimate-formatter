<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ArrayKeyExistsTernaryToCoalesce\Fixture;

class CoalescesKeyExistsTernaries
{
    /**
     * @param array<string, string> $address
     */
    public function run(array $address, string $key, string $default): array
    {
        $city = array_key_exists('city', $address) ? $address['city'] : '';
        $value = array_key_exists($key, $address) ? $address[$key] : $default;
        $zip = !array_key_exists('zip', $address) ? null : $address['zip'];

        return [$city, $value, $zip];
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ArrayKeyExistsTernaryToCoalesce\Fixture;

class CoalescesKeyExistsTernaries
{
    /**
     * @param array<string, string> $address
     */
    public function run(array $address, string $key, string $default): array
    {
        $city = $address['city'] ?? '';
        $value = $address[$key] ?? $default;
        $zip = $address['zip'] ?? null;

        return [$city, $value, $zip];
    }
}

?>
