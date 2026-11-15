# Ultimate Formatter for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/adamczykpiotr/laravel-ultimate-formatter.svg?style=flat-square)](https://packagist.org/packages/adamczykpiotr/laravel-ultimate-formatter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/adamczykpiotr/laravel-ultimate-formatter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/adamczykpiotr/laravel-ultimate-formatter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/adamczykpiotr/laravel-ultimate-formatter.svg?style=flat-square)](https://packagist.org/packages/adamczykpiotr/laravel-ultimate-formatter)

Ultimate code formatter for Laravel, taking care of everything: one package that ships Rector
and PHP CS Fixer with a shared, opinionated configuration, so every project formats its code the
same way. A PHPStan ruleset reports what the formatters can't fix: banned calls and architecture
rules.

## Installation

```bash
composer require --dev adamczykpiotr/laravel-ultimate-formatter
vendor/bin/ultimate-formatter install
```

Supports Laravel 12–13 on PHP 8.3+.

`install` detects whether the project is an application or a package and publishes `.php-cs-fixer.php`,
`rector.php`, `phpstan.neon` and `.git-blame-ignore-revs`, plus `config/ultimate-formatter.php` in an
application. Existing files are skipped unless you pass `--force`, and `--ci` adds a GitHub Actions
workflow. See [Setup](docs/setup.md) for the paths it picks and how to add the ruleset to an existing
PHPStan config, and [Adopting it in an existing project](docs/adopting.md) for the first run on a
large codebase.

## Usage

```bash
vendor/bin/ultimate-formatter format    # Rector, then PHP CS Fixer
vendor/bin/ultimate-formatter check     # dry run of both, then PHPStan; stops at the first failure (CI)
```

`check` runs PHPStan only when the project has a `phpstan.neon`, `phpstan.neon.dist` or `phpstan.dist.neon`.
Before anything else it asks PHPStan whether that config loads the [PHPStan ruleset](docs/phpstan-ruleset.md),
and fails with the includes to add when it doesn't, since the ruleset's rules would silently never run.
Pass `--without-phpstan-ruleset` to run PHPStan without it.

`format --models` regenerates model docblocks with laravel-ide-helper first, which is installed with the package. In a Laravel app the
same commands are available as `php artisan formatter:install`, `formatter:format` and
`formatter:check`.

| Flag | Effect |
|---|---|
| `-q` | Silences every step, so only the exit code reports the result |
| `-v`, `-vv` | Prints each command before it runs and passes the same level to each step |
| `-vvv` | As above, and passes `--debug` to Rector, which has no other verbosity flags |

These flags replace any `--quiet` or `-v` in the [configured arguments](docs/configuration.md#tool-arguments).

## What it enforces

- **PHP CS Fixer:** strict types, next-line braces, ordered imports and docblock tags, one sorted trait statement.
- **Rector:** type declarations, Laravel sets, `match`, arrow functions, Carbon, and debug calls removed.
- **Custom rules:** grouped numeric literals, `=== false`/`=== null` instead of `!`, interpolation, early `continue` guards, and more.
- **PHPStan ruleset:** banned calls, class naming and architecture rules, and migration conventions.

## Documentation

- [Setup](docs/setup.md): layout detection, published files, PHPStan and Larastan, CI
- [Adopting it in an existing project](docs/adopting.md): the first format, blame, and getting PHPStan to pass
- [Configuration](docs/configuration.md): tool arguments, formatter rules, pre steps, ruleset flags, performance
- [Upgrading](docs/upgrading.md): how `migrate-config` keeps your published config current
- [Formatting rules](docs/rules.md): everything `format` rewrites
- [PHPStan ruleset](docs/phpstan-ruleset.md): every rule the ruleset reports, with its identifier

### Upstream drift

Rector drops rules from its sets in minor releases without a warning. `tests/Drift/Snapshot/rector-rules.txt` lists the Rector rules after the sets are expanded, with deprecated ones marked, so a dropped or deprecated rule shows up as a diff.

```bash
composer test:drift       # compare
composer snapshot:update  # rewrite the snapshot, then review the diff
```

The snapshot depends on the installed Laravel version, so `composer test` leaves it out. The `upstream-drift` workflow runs it on every pull request and weekly.

## Changelog

See [CHANGELOG](CHANGELOG.md) for what changed in each release.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for running the tests and how releases work.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
