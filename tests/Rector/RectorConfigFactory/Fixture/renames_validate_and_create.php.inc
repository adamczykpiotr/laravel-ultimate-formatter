<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

use Spatie\LaravelData\Data;

class StoreTagInput extends Data
{
}

class RenamesValidateAndCreate
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return StoreTagInput
     */
    public function input(array $payload): StoreTagInput
    {
        return StoreTagInput::validateAndCreate($payload);
    }
}
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

use Spatie\LaravelData\Data;

class StoreTagInput extends Data
{
}

class RenamesValidateAndCreate
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return StoreTagInput
     */
    public function input(array $payload): StoreTagInput
    {
        return StoreTagInput::from($payload);
    }
}
