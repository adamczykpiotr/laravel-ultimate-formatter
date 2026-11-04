<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class MigrationRules
{
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
