<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ControllerRules
{
    /**
     * @return Rule
     */
    public function testControllersAreNamedController(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Http\Controllers'))
            ->excluding(Selector::isAbstract())
            ->should()->beNamed('/Controller$/', regex: true)
            ->because('controllers are named {Name}Controller.');
    }
}
