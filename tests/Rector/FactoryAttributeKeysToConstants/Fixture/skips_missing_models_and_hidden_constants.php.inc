<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Fixture;

use AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Label;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissingModel>
 */
class GhostFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'x'];
    }
}

class SecretLabelFactory extends Factory
{
    protected $model = Label::class;

    public function definition(): array
    {
        return ['secret' => 'y'];
    }
}

?>
