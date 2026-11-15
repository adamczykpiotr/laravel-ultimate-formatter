<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\ArrayKeyExistsTernaryToCoalesce\Fixture;

class SkipsMismatchedTernaries
{
    /**
     * @param array<string, string> $address
     * @param array<string, string> $fallback
     */
    public function run(array $address, array $fallback): array
    {
        $other = array_key_exists('city', $address) ? $fallback['city'] : '';
        $key = array_key_exists('city', $address) ? $address['zip'] : '';
        $short = array_key_exists('city', $address) ?: false;

        return [$other, $key, $short];
    }
}

?>
