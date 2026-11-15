<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * @var class-string<Tag>
     */
    protected $model = Tag::class;

    /**
     * @return array<model-property<Tag>, mixed>
     */
    public function definition(): array
    {
        return [];
    }
}
