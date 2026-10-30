<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ArchitectureRules
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
    public function testResourcesAreNamedResource(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Http\Resources'))
            ->excluding(Selector::isAbstract())
            ->should()->beNamed('/Resource$/', regex: true)
            ->because('resources are named {Name}Resource.');
    }

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

    /**
     * @return Rule
     */
    public function testServicesDoNotDependOnHttp(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Services'))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('Illuminate\Http'))
            ->because('a service behaves the same whether a controller, a job, a command or a test calls it.');
    }

    /**
     * @return Rule
     */
    public function testMigrationsDoNotDependOnModels(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::withFilepath('#/database/migrations/#', regex: true))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('App\Models'))
            ->because('a migration must keep working after the model changes, so it uses the query builder.');
    }
}
