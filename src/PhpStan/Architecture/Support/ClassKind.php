<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture\Support;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

class ClassKind
{
    /**
     * phpat selects interfaces and enums along with classes, and doesn't count an interface as abstract.
     *
     * @return SelectorInterface
     */
    public static function notClass(): SelectorInterface
    {
        return Selector::AnyOf(Selector::isInterface(), Selector::isEnum());
    }

    /**
     * @return SelectorInterface
     */
    public static function notConcreteClass(): SelectorInterface
    {
        return Selector::AnyOf(Selector::isAbstract(), self::notClass());
    }
}
