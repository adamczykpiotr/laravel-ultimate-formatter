# Ultimate Formatter for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/adamczykpiotr/laravel-ultimate-formatter.svg?style=flat-square)](https://packagist.org/packages/adamczykpiotr/laravel-ultimate-formatter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/adamczykpiotr/laravel-ultimate-formatter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/adamczykpiotr/laravel-ultimate-formatter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/adamczykpiotr/laravel-ultimate-formatter.svg?style=flat-square)](https://packagist.org/packages/adamczykpiotr/laravel-ultimate-formatter)

Ultimate code formatter for Laravel, taking care of everything: one package that ships Rector
and PHP CS Fixer with a shared, opinionated configuration, so every project formats its code the
same way.

## Installation

```bash
composer require --dev adamczykpiotr/laravel-ultimate-formatter
```

Supports Laravel 12–13 on PHP 8.3+.

## Setup

Publish the configs into the project root:

```bash
vendor/bin/ultimate-formatter install
```

The command detects the layout and fills in the paths that exist:

| Layout | Detected by | PHP CS Fixer | Rector |
|---|---|---|---|
| Application | `artisan` and `app/` | `app`, `tests` | `app`, `database`, `tests` |
| Package | `src/` | `config`, `database`, `src`, `tests`, `workbench` | `config`, `database`, `src`, `tests`, `workbench` |

It writes `.php-cs-fixer.php`, `rector.php` and `.git-blame-ignore-revs`. In an application it also publishes `config/ultimate-formatter.php`.
Existing files are skipped unless you pass `--force`. `.git-blame-ignore-revs` is never overwritten, since it holds your recorded commits. Rector skips `database/migrations`.

It also adds the `check` and `format` scripts to `composer.json`, keeping any scripts with those names that already exist.
In a package it adds `export-ignore` entries to `.gitattributes` for the files it publishes, so they stay out of the dist archive.

Pass `--ci` to also publish `.github/workflows/code-style.yml`, which runs `composer check` on the minimum PHP version in `composer.json`.
Running PHP CS Fixer on a newer PHP could introduce syntax the minimum doesn't support.

## Usage

```bash
vendor/bin/ultimate-formatter format    # Rector, then PHP CS Fixer
vendor/bin/ultimate-formatter check     # dry run, Rector first; stops at the first pending change (CI)
```

`format --models` regenerates model docblocks with
[laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) first. In a Laravel app the
same commands are available as `php artisan formatter:install`, `formatter:format`
and `formatter:check`.

Record bulk formatting commits in `.git-blame-ignore-revs` so blame skips them, and point
`blame.ignoreRevsFile` at that file in your local git config.

## Customising

The arguments each command passes to its tools live in `config/ultimate-formatter.php`. Override a
tool's list there, and the tools you leave out keep the package defaults:

```php
return [
    'check' => [
        'php-cs-fixer' => ['fix', '--dry-run', '--diff', '--verbose'],
    ],
];
```

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

### Pre steps

`format` can run artisan steps before the tools. Turn them on in `config/ultimate-formatter.php`:

```php
'pre' => [
    'clear-cache' => true, // optimize:clear, so the steps below see the current .env
    'migrate' => true,     // migrate --force, so ide-helper:models documents every column
],
```

They run in that order, then `--models`, then Rector and PHP CS Fixer, and the first failing step stops
the run. Their arguments live under `format.clear-cache` and `format.migrate`. `check` never runs them.

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

## What it enforces

- **PHP CS Fixer.** `declare(strict_types=1)`, next-line braces for classes and methods, ordered
  imports, and ordered `@param`/`@return`/`@throws` tags.
- **Rector.** Type declarations, docblock types, and the Laravel quality, collection and type sets
  matched to the installed Laravel version.
- **Docblocks.** Every method gets `@param`/`@return` tags in line with its signature. Narrower tags
  such as `non-empty-string` or `list<string>` are kept. `?string` in a tag becomes `string|null`.
- **Banner comments.** A `/* | Section | */` banner is always followed by a blank line, via the
  custom `UltimateFormatter/blank_line_after_banner_comment` fixer.

## Testing

```bash
composer test
composer analyse
```

## Changelog

Releases are tagged automatically by semantic-release from the conventional commit titles merged into `main`. See [CHANGELOG](CHANGELOG.md) for what changed.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
