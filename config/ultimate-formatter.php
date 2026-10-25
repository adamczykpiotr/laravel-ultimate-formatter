<?php

declare(strict_types=1);

return [
    'format' => [
        'ide-helper' => ['ide-helper:models', '--reset', '--write', '--quiet'],
        'rector' => ['process', '--no-progress-bar', '--no-diffs'],
        'php-cs-fixer' => ['fix', '--quiet'],
    ],
    'check' => [
        'rector' => ['process', '--dry-run', '--no-progress-bar'],
        'php-cs-fixer' => ['fix', '--dry-run', '--diff'],
    ],
];
