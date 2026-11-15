<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

class ChangesSwitchToMatch
{
    /**
     * @param string $status
     *
     * @return string
     */
    public function label(string $status): string
    {
        switch ($status) {
            case 'draft':
                return 'Draft';
            case 'published':
                return 'Published';
            default:
                return 'Unknown';
        }
    }
}
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\RectorConfigFactory\Fixture;

class ChangesSwitchToMatch
{
    /**
     * @param string $status
     *
     * @return string
     */
    public function label(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'published' => 'Published',
            default => 'Unknown',
        };
    }
}
