<?php

declare(strict_types=1);

// Arguments each formatter command passes to its tools. A project overrides a tool's list in its own
// config/ultimate-formatter.php; tools it leaves out keep these defaults.
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
