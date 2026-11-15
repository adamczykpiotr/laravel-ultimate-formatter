<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class RequestRules
{
    /**
     * @return Rule
     */
    public function testNoClassExtendsFormRequest(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App'))
            ->shouldNot()->extend()
            ->classes(Selector::classname('Illuminate\Foundation\Http\FormRequest'))
            ->because('input DTOs extending BaseInput validate the request instead.');
    }
}
