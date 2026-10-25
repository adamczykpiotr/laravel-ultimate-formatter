<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class KeepsBlankLineAfterSectionComment
{
    /*
    |========================================
    | Factory Methods
    |========================================
    */

    /**
     * Create an instance.
     */
    public static function make(): self
    {
        return new self();
    }

    // Accessors

    /**
     * Name.
     */
    public function name(): string
    {
        return 'name';
    }

    // Mutators

    public function rename(string $name): void
    {
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\NormalizeMethodDocblock\Fixture;

class KeepsBlankLineAfterSectionComment
{
    /*
    |========================================
    | Factory Methods
    |========================================
    */
%w
    /**
     * Create an instance.
     * @return self
     */
    public static function make(): self
    {
        return new self();
    }

    // Accessors
%w
    /**
     * Name.
     * @return string
     */
    public function name(): string
    {
        return 'name';
    }

    // Mutators
%w
    /**
     * @param string $name
     * @return void
     */
    public function rename(string $name): void
    {
    }
}

?>
