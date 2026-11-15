<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ExceptionRules
{
    /**
     * @return Rule
     */
    public function testExceptionsAreNamedException(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(Selector::inNamespace('App'), Selector::isException()))
            ->should()->beNamed('/Exception$/', regex: true)
            ->because('custom exceptions are named {Description}Exception.');
    }

    /**
     * @return Rule
     */
    public function testExceptionsExtendBaseException(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(Selector::inNamespace('App'), Selector::isException()))
            ->excluding(Selector::inNamespace('App\Exceptions\Base'))
            ->should()->extend()
            ->classes(Selector::classname('App\Exceptions\Base\BaseException'))
            ->because('the exception handler maps BaseException subclasses to an HTTP code and a ResponseType.');
    }
}
