# Setup

Publish the configs into the project root:

```bash
vendor/bin/ultimate-formatter install
```

The command detects the layout and fills in the paths that exist:

| Layout | Detected by | PHP CS Fixer | Rector | PHPStan |
|---|---|---|---|---|
| Application | `artisan` and `app/` | `app`, `config`, `database`, `routes`, `tests` | `app`, `config`, `database`, `routes`, `tests` | `app`, `database`, `routes`, `tests` |
| Package | `src/` | `config`, `database`, `src`, `tests`, `workbench` | `config`, `database`, `src`, `tests`, `workbench` | `database`, `src`, `tests`, `workbench` |

## Published files

It writes `.php-cs-fixer.php`, `rector.php`, `phpstan.neon` and `.git-blame-ignore-revs`. In an application it also publishes `config/ultimate-formatter.php`
and `config/ide-helper.php`. The ide-helper config holds only the settings that differ from
[laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper)'s defaults (no `where{Column}()` magic methods),
since ide-helper merges its own defaults under it. Add your ignored models and model hooks there.
Existing files are skipped unless you pass `--force`. `.git-blame-ignore-revs` is never overwritten, since it holds your recorded commits.

Record bulk formatting commits in `.git-blame-ignore-revs` so blame skips them, and point
`blame.ignoreRevsFile` at that file in your local git config.

## PHPStan

`phpstan.neon` runs level `max` with the [PHPStan ruleset](phpstan-ruleset.md). It is never written next to a
`phpstan.neon.dist` or `phpstan.dist.neon`, since it would shadow that config. Add the ruleset to your
existing config instead:

```neon
includes:
    - vendor/adamczykpiotr/laravel-ultimate-formatter/phpstan-rules.neon
```

The ruleset configures Larastan, phpstan-disallowed-calls and phpat, so they have to be loaded. With
`phpstan/extension-installer` they load themselves. Without it, the generated `phpstan.neon` includes
their `extension.neon` files.

The package requires Larastan `^3.13`. A project on an older Larastan gets upgraded with it, and the
newer Larastan can report errors of its own, such as `larastan.relationExistence` or
`method.alreadyNarrowedType`. Those come from Larastan, not from the ruleset, so fix or baseline them
separately.

## composer.json and .gitattributes

It also adds the `check` and `format` scripts to `composer.json`, keeping any scripts with those names that already exist.
In an application it adds `vendor/bin/ultimate-formatter migrate-config` to `post-update-cmd`, so the published config
[follows each upgrade](upgrading.md).
In a package it adds `export-ignore` entries to `.gitattributes` for the files it publishes, so they stay out of the dist archive.

## CI

Pass `--ci` to also publish `.github/workflows/code-style.yml`, which runs `composer check` on the minimum PHP version in `composer.json`.
Running PHP CS Fixer on a newer PHP could introduce syntax the minimum doesn't support.
