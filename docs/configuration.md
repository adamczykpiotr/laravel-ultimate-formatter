# Configuration

## Tool arguments

The arguments each command passes to its tools live in `config/ultimate-formatter.php`. Override a
tool's list there, and the tools you leave out keep the package defaults:

```php
return [
    'check' => [
        'php-cs-fixer' => ['fix', '--dry-run', '--diff', '--verbose'],
        'phpstan' => ['analyse', '--no-progress', '--memory-limit=4G'],
    ],
];
```

## Formatter rules

The generated files are yours to edit. Rules are merged in, not replaced:

```php
// .php-cs-fixer.php
return FixerConfigFactory::create($finder, __DIR__ . '/build/.php-cs-fixer.cache')
    ->setRules([...FixerConfigFactory::rules(), 'yoda_style' => true]);

// rector.php
return RectorConfigFactory::create()
    ->withPaths([__DIR__ . '/src'])
    ->withSkip([SomeRector::class]);
```

## Pre steps

`format` can run artisan steps before the tools. Turn them on in `config/ultimate-formatter.php`:

```php
'pre' => [
    'clear-cache' => true, // optimize:clear, so the steps below see the current .env
    'migrate' => true,     // migrate --force, so ide-helper:models documents every column
],
```

They run in that order, then `--models`, then Rector and PHP CS Fixer, and the first failing step stops
the run. Their arguments live under `format.clear-cache` and `format.migrate`. `check` never runs them.

## Ruleset flags

Each rule of the [PHPStan ruleset](phpstan-ruleset.md) has a flag in `config/ultimate-formatter.php`,
keyed by the identifier PHPStan reports. Set one to `false` to adopt the ruleset now and fix that rule
later:

```php
'phpstan-rules' => [
    'ultimateFormatter.disallowNativeArrayFunctions' => false,
    'phpat.testServicesAreFinal' => false,
    'phpat.testServicesAreNamedService' => true,
],
```

Rules you leave out keep their default. `phpat.testServicesAreNamedService` is off by default, since
`App\Services` often holds mappers and similar classes. Only the ruleset's own rules have flags; ignore
any other error with `ignoreErrors`. A key that isn't one of them, or a value that isn't `true` or
`false`, stops PHPStan with an error, so a typo can't leave a rule on unnoticed. PHPStan reads the flags
on every run, so a change takes effect without clearing its cache.

## Performance

The package is tuned to make every run as fast as it can be:

- **Rector turbo.** Requires Rector 2.8+, the release with the turbo performance improvements.
- **PHPStan turbo.** Rector 2.8 pulls in PHPStan 2.3+, so its type analysis runs on the faster
  PHPStan too.
- **PHP CS Fixer on every core.** It runs in parallel on every available core, without reserving one
  for the main process, so 2-core CI runners don't fall back to a sequential run. Cgroup quotas and
  `KUBERNETES_CPU_LIMIT` still cap the count.

Rector runs 4 parallel processes by default to stay within container memory limits. Raise it in
`rector.php` with `->withParallel(maxNumberOfProcess: 8)` when the machine has room.
