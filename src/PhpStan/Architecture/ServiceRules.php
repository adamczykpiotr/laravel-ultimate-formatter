<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ServiceRules
{
    /**
     * @return Rule
     */
    public function testServicesAreNamedService(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Services'))
            ->excluding(Selector::isAbstract(), Selector::isInterface(), Selector::isEnum())
            ->should()->beNamed('/Service$/', regex: true)
            ->because('services are named {Name}Service.');
    }

    /**
     * @return Rule
     */
    public function testServicesAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Services'))
            ->excluding(
                Selector::isAbstract(),
                Selector::isInterface(),
                Selector::isEnum(),
                Selector::isTrait(),
                Selector::classname('/\\\\Base[A-Z][^\\\\]*$/', regex: true),
            )
            ->should()->beFinal()
            ->because('services are composed, not extended; only a Base{Name} class is meant to be extended.');
    }

    /**
     * @return Rule
     */
    public function testServicesAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Services'))
            ->excluding(Selector::isInterface(), Selector::isEnum(), Selector::isTrait())
            ->should()->beReadonly()
            ->because('services are stateless, so the same instance can serve every call.');
    }

    /**
     * @return Rule
     */
    public function testServicesDoNotDependOnRequestResponse(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Services'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('Illuminate\Http'),
                Selector::inNamespace('Illuminate\Session'),
                Selector::inNamespace('Illuminate\Contracts\Session'),
                // The guard logs users in and out through the session; Authenticatable, the user passed in, stays allowed.
                Selector::classname('Illuminate\Auth\AuthManager'),
                Selector::classname('Illuminate\Auth\SessionGuard'),
                Selector::classname('Illuminate\Contracts\Auth\Factory'),
                Selector::classname('Illuminate\Contracts\Auth\Guard'),
                Selector::classname('Illuminate\Contracts\Auth\StatefulGuard'),
                Selector::classname('Illuminate\Support\Facades\Auth'),
                Selector::classname('Illuminate\Support\Facades\Cookie'),
                Selector::classname('Illuminate\Support\Facades\Redirect'),
                Selector::classname('Illuminate\Support\Facades\Request'),
                Selector::classname('Illuminate\Support\Facades\Response'),
                Selector::classname('Illuminate\Support\Facades\Session'),
            )
            ->excluding(Selector::inNamespace('Illuminate\Http\Client'))
            ->because('a service behaves the same whether a controller, a job, a command or a test calls it.');
    }
}
