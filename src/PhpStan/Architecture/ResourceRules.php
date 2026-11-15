<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use AdamczykPiotr\UltimateFormatter\PhpStan\Architecture\Support\ClassKind;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ResourceRules
{
    /**
     * @return Rule
     */
    public function testResourcesAreNamedResource(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Http\Resources'))
            ->excluding(ClassKind::notConcreteClass())
            ->should()->beNamed('/Resource$/', regex: true)
            ->because('resources are named {Name}Resource.');
    }
}
