<?php

declare(strict_types=1);

return [
    // Artisan steps `format` runs before the tools, in this order. They need a booted app, so apps only.
    'pre' => [
        // Clears cached config first, so the steps below see the current .env.
        'clear_cache' => false,
        // Brings the schema up to date, so ide-helper:models documents every column.
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
    ],
];
