<?php

declare(strict_types=1);

return [
    'pre' => [
        'clear-cache' => false,
        'migrate' => false,
    ],
    'format' => [
        'clear-cache' => ['optimize:clear', '--quiet'],
        'migrate' => ['migrate', '--force'],
        'ide-helper' => ['ide-helper:models', '--reset', '--write', '--quiet'],
        'rector' => ['process', '--no-progress-bar', '--no-diffs'],
        'php-cs-fixer' => ['fix', '--quiet'],
    ],
    'check' => [
        'rector' => ['process', '--dry-run', '--no-progress-bar'],
        'php-cs-fixer' => ['fix', '--dry-run', '--diff'],
        'phpstan' => ['analyse', '--no-progress', '--memory-limit=2G'],
    ],
    // The PHPStan ruleset's architecture rules; set one to false to turn it off.
    'architecture' => [
        'testControllersAreNamedController' => true,
        'testServicesAreNamedService' => false,
        'testServicesAreFinal' => true,
        'testServicesAreReadonly' => true,
        'testServicesDoNotDependOnHttp' => true,
        'testResourcesAreNamedResource' => true,
        'testInputDtosAreNamedInput' => true,
        'testInputDtosExtendBaseInput' => true,
        'testOutputDtosAreNamedDto' => true,
        'testNoClassExtendsFormRequest' => true,
        'testExceptionsAreNamedException' => true,
        'testExceptionsExtendBaseException' => true,
        'testMigrationsDoNotDependOnModels' => true,
    ],
];
