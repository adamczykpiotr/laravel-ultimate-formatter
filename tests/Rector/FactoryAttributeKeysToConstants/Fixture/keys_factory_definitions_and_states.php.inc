<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Fixture;

use AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Label;
use AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class KeysTagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Urgent',
            'color' => 'red',
            'priority' => 1,
        ];
    }

    public function blue(): self
    {
        return $this->state(fn(array $attributes): array => ['color' => 'blue']);
    }
}

class KeysLabelFactory extends Factory
{
    protected $model = Label::class;

    public function definition(): array
    {
        return ['title' => 'Inbox', 'name' => 'unknown'];
    }
}

class CreatesTags
{
    public function run(): void
    {
        Tag::factory()->create(['name' => 'Urgent']);
        Tag::factory()->state(function (): array {
            return ['color' => 'green'];
        })->make();
        (new KeysLabelFactory())->make(['title' => 'Archive']);
    }
}

?>
-----
<?php

namespace AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Fixture;

use AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Label;
use AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class KeysTagFactory extends Factory
{
    public function definition(): array
    {
        return [
            \AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Tag::ATTRIBUTE_NAME => 'Urgent',
            \AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Tag::ATTRIBUTE_COLOR => 'red',
            'priority' => 1,
        ];
    }

    public function blue(): self
    {
        return $this->state(fn(array $attributes): array => [\AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Tag::ATTRIBUTE_COLOR => 'blue']);
    }
}

class KeysLabelFactory extends Factory
{
    protected $model = Label::class;

    public function definition(): array
    {
        return [\AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Label::ATTRIBUTE_TITLE => 'Inbox', 'name' => 'unknown'];
    }
}

class CreatesTags
{
    public function run(): void
    {
        Tag::factory()->create([\AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Tag::ATTRIBUTE_NAME => 'Urgent']);
        Tag::factory()->state(function (): array {
            return [\AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Tag::ATTRIBUTE_COLOR => 'green'];
        })->make();
        (new KeysLabelFactory())->make([\AdamczykPiotr\UltimateFormatter\Tests\Rector\FactoryAttributeKeysToConstants\Source\Label::ATTRIBUTE_TITLE => 'Archive']);
    }
}

?>
