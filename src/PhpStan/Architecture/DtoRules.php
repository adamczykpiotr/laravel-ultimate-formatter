<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class DtoRules
{
    /**
     * @return Rule
     */
    public function testInputDtosAreNamedInput(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^App\\\Dto\\\.+\\\Input$/', regex: true))
            ->should()->beNamed('/Input$/', regex: true)
            ->because('input DTOs are named {Action}Input and live under App\Dto\{Domain}\Input.');
    }

    /**
     * @return Rule
     */
    public function testOutputDtosAreNamedDto(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Dto'))
            ->excluding(
                Selector::inNamespace('/^App\\\Dto\\\.+\\\Input$/', regex: true),
                Selector::classname('App\Dto\BaseInput'),
                Selector::isAbstract(),
                Selector::isInterface(),
            )
            ->should()->beNamed('/Dto$/', regex: true)
            ->because('output DTOs are named {Name}Dto.');
    }

    /**
     * @return Rule
     */
    public function testInputDtosExtendBaseInput(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^App\\\Dto\\\.+\\\Input$/', regex: true))
            ->excluding(Selector::isAbstract())
            ->should()->extend()
            ->classes(Selector::classname('App\Dto\BaseInput'))
            ->because('BaseInput merges the manual validation rules with the inferred ones.');
    }
}
