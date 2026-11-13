<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class DtoRules
{
    protected const INPUT_NAMESPACE_PATTERN = '/^App\\\Dto\\\.+\\\Input(\\\.+)?$/';
    protected const BASE_INPUT_CLASS = 'App\Dto\BaseInput';
    protected const CAST_INTERFACE = 'Spatie\LaravelData\Casts\Cast';
    protected const TRANSFORMER_INTERFACE = 'Spatie\LaravelData\Transformers\Transformer';

    /**
     * @return Rule
     */
    public function testInputDtosAreNamedInput(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::INPUT_NAMESPACE_PATTERN, regex: true))
            ->should()->beNamed('/Input$/', regex: true)
            ->because('input DTOs are named {Action}Input and live under App\Dto\{Domain}\Input or a namespace below it.');
    }

    /**
     * @return Rule
     */
    public function testOutputDtosAreNamedDto(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Dto'))
            ->excluding(
                Selector::inNamespace(self::INPUT_NAMESPACE_PATTERN, regex: true),
                Selector::classname(self::BASE_INPUT_CLASS),
                Selector::implements(self::CAST_INTERFACE),
                Selector::implements(self::TRANSFORMER_INTERFACE),
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
            ->classes(Selector::inNamespace(self::INPUT_NAMESPACE_PATTERN, regex: true))
            ->excluding(Selector::isAbstract())
            ->should()->extend()
            ->classes(Selector::classname(self::BASE_INPUT_CLASS))
            ->because('BaseInput merges the manual validation rules with the inferred ones.');
    }
}
