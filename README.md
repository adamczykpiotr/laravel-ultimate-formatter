# Ultimate Formatter for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/adamczykpiotr/laravel-ultimate-formatter.svg?style=flat-square)](https://packagist.org/packages/adamczykpiotr/laravel-ultimate-formatter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/adamczykpiotr/laravel-ultimate-formatter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/adamczykpiotr/laravel-ultimate-formatter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/adamczykpiotr/laravel-ultimate-formatter.svg?style=flat-square)](https://packagist.org/packages/adamczykpiotr/laravel-ultimate-formatter)

Ultimate code formatter for Laravel, taking care of everything: one package that ships Rector,
PHP CS Fixer and PHPStan/Larastan with a shared, opinionated configuration, so every project
formats and analyses its code the same way.

## Installation

```bash
composer require --dev adamczykpiotr/laravel-ultimate-formatter
```

Supports Laravel 11–13 on PHP 8.3+.

## Setup

Publish the configs into the project root:

```bash
vendor/bin/ultimate-formatter install
```

The command detects the layout and fills in the paths that exist:

| Layout | Detected by | PHP CS Fixer | Rector | PHPStan | PHPStan config |
|---|---|---|---|---|---|
| Application | `artisan` and `app/` | `app`, `tests` | `app`, `database`, `tests` | `app` | `phpstan.neon` |
| Package | `src/` | `src`, `tests` | `src`, `tests` | `src`, `tests` | `phpstan.neon.dist` |

It writes `.php-cs-fixer.php`, `rector.php`, the PHPStan config, `.editorconfig`,
the `code-style.yml` and `phpstan.yml` workflows, and `.git-blame-ignore-revs`. Existing files are skipped
unless you pass `--force`. In an application, Rector skips `database/migrations`.

## Usage

```bash
vendor/bin/ultimate-formatter format    # Rector, then PHP CS Fixer
vendor/bin/ultimate-formatter check     # dry run of both, fails on any change (CI)
vendor/bin/ultimate-formatter analyse   # PHPStan
```

`format --models` regenerates model docblocks with
[laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) first. In a Laravel app the
same commands are available as `php artisan formatter:install`, `formatter:format`,
`formatter:check` and `formatter:analyse`.

Record bulk formatting commits in `.git-blame-ignore-revs` so blame skips them, and point
`blame.ignoreRevsFile` at that file in your local git config.

## Customising

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

The PHPStan config includes `phpstan/base.neon` from this package, which sets `level: max`. Lower
it or add your own parameters below the include.

## What it enforces

- **PHP CS Fixer.** `declare(strict_types=1)`, next-line braces for classes and methods, ordered
  imports, and ordered `@param`/`@return`/`@throws` tags.
- **Rector.** Type declarations, docblock types, and the Laravel quality, collection and type sets
  matched to the installed Laravel version.
- **Docblocks.** Every method gets `@param`/`@return` tags in line with its signature. Narrower tags
  such as `non-empty-string` or `list<string>` are kept. `?string` in a tag becomes `string|null`.

## Testing

```bash
composer test
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
