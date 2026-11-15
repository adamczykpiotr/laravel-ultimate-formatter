<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

class UsesCarbonForDates
{
    /**
     * @return string
     */
    public function today(): string
    {
        return date('Y-m-d');
    }

    /**
     * @return \DateTime
     */
    public function now(): \DateTime
    {
        return new \DateTime();
    }
}
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

use Illuminate\Support\Facades\Date;
use DateTime;

class UsesCarbonForDates
{
    /**
     * @return string
     */
    public function today(): string
    {
        return Date::now()->format('Y-m-d');
    }

    /**
     * @return DateTime
     */
    public function now(): DateTime
    {
        return Date::now();
    }
}
