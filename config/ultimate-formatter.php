<?php

declare(strict_types=1);

return [
    'config-version' => '1.12.0',

    'pre' => [
        'clear-cache' => false,
        'migrate' => false,
    ],
    'format' => [
        'clear-cache' => ['optimize:clear', '--quiet'],
        'migrate' => ['migrate', '--force'],
        'ide-helper' => ['ide-helper:models', '--reset', '--write', '--quiet'],
        'rector' => ['process', '--no-progress-bar', '--no-diffs'],
        'php-cs-fixer' => ['fix', '--show-progress=none'],
    ],
    'check' => [
        'rector' => ['process', '--dry-run', '--no-progress-bar'],
        'php-cs-fixer' => ['fix', '--dry-run', '--diff'],
        'phpstan' => ['analyse', '--no-progress', '--memory-limit=2G'],
    ],
    // The PHPStan ruleset's rules, by the identifier PHPStan reports; set one to false to turn it off.
    'phpstan-rules' => [
        /*
        |--------------------------------------------------------------------------
        | Banned calls
        |--------------------------------------------------------------------------
        */

        'ultimateFormatter.disallowSprintf' => true,
        'ultimateFormatter.disallowNativeArrayFunctions' => true,
        'ultimateFormatter.disallowMethodExists' => true,
        'ultimateFormatter.disallowFileExists' => true,
        'ultimateFormatter.disallowNativePregFunctions' => true,
        'ultimateFormatter.disallowRequestResponseHelpersInServices' => true,
        'ultimateFormatter.disallowModelMassAssignment' => true,
        'ultimateFormatter.disallowResponseJson' => true,
        'ultimateFormatter.disallowAppEnvironment' => true,
        'ultimateFormatter.disallowLogShouldReceive' => true,

        /*
        |--------------------------------------------------------------------------
        | Migrations
        |--------------------------------------------------------------------------
        */

        'ultimateFormatter.migrationTimestampsLast' => true,
        'ultimateFormatter.migrationUpAndDown' => true,
        'ultimateFormatter.migrationLiteralSchemaNames' => true,
        'ultimateFormatter.migrationSchemaClosure' => true,
        'ultimateFormatter.migrationForeignIdConstrained' => true,

        /*
        |--------------------------------------------------------------------------
        | Resources, enums, queued classes and models
        |--------------------------------------------------------------------------
        */

        'ultimateFormatter.resourceKeysCamelCase' => true,
        'ultimateFormatter.enumCasesUpperSnakeCase' => true,
        'ultimateFormatter.enumValuesMatchCaseNames' => true,
        'ultimateFormatter.queuedClassNoPrivateProperties' => true,
        'ultimateFormatter.modelNoWith' => true,
        'ultimateFormatter.modelNoAppends' => true,

        /*
        |--------------------------------------------------------------------------
        | Larastan
        |--------------------------------------------------------------------------
        */

        'larastan.noEnvCallsOutsideOfConfig' => true,

        /*
        |--------------------------------------------------------------------------
        | Architecture (phpat)
        |--------------------------------------------------------------------------
        */

        'phpat.testControllersAreNamedController' => true,
        'phpat.testServicesAreNamedService' => false,
        'phpat.testServicesAreFinal' => true,
        'phpat.testServicesAreReadonly' => true,
        'phpat.testServicesDoNotDependOnRequestResponse' => true,
        'phpat.testResourcesAreNamedResource' => true,
        'phpat.testInputDtosAreNamedInput' => true,
        'phpat.testInputDtosExtendBaseInput' => true,
        'phpat.testOutputDtosAreNamedDto' => true,
        'phpat.testNoClassExtendsFormRequest' => true,
        'phpat.testExceptionsAreNamedException' => true,
        'phpat.testExceptionsExtendBaseException' => true,
        'phpat.testMigrationsDoNotDependOnModels' => true,
    ],
];
