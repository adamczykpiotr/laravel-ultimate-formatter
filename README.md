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
```

Supports Laravel 12–13 on PHP 8.3+.

## Setup

Publish the configs into the project root:

```bash
vendor/bin/ultimate-formatter install
```

The command detects the layout and fills in the paths that exist:

| Layout | Detected by | PHP CS Fixer | Rector | PHPStan |
|---|---|---|---|---|
| Application | `artisan` and `app/` | `app`, `tests` | `app`, `database`, `tests` | `app`, `database`, `routes`, `tests` |
| Package | `src/` | `config`, `database`, `src`, `tests`, `workbench` | `config`, `database`, `src`, `tests`, `workbench` | `database`, `src`, `tests`, `workbench` |

It writes `.php-cs-fixer.php`, `rector.php`, `phpstan.neon` and `.git-blame-ignore-revs`. In an application it also publishes `config/ultimate-formatter.php`
and `config/ide-helper.php`. The ide-helper config holds only the settings that differ from
[laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper)'s defaults (no `where{Column}()` magic methods),
since ide-helper merges its own defaults under it. Add your ignored models and model hooks there.
Existing files are skipped unless you pass `--force`. `.git-blame-ignore-revs` is never overwritten, since it holds your recorded commits. Rector skips `database/migrations`.

`phpstan.neon` runs level `max` with the [PHPStan ruleset](#phpstan-ruleset). It is never written next to a
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

It also adds the `check` and `format` scripts to `composer.json`, keeping any scripts with those names that already exist.
In an application it adds `vendor/bin/ultimate-formatter migrate-config` to `post-update-cmd`, so the published config
[follows each upgrade](#upgrading).
In a package it adds `export-ignore` entries to `.gitattributes` for the files it publishes, so they stay out of the dist archive.

Pass `--ci` to also publish `.github/workflows/code-style.yml`, which runs `composer check` on the minimum PHP version in `composer.json`.
Running PHP CS Fixer on a newer PHP could introduce syntax the minimum doesn't support.

## Usage

```bash
vendor/bin/ultimate-formatter format    # Rector, then PHP CS Fixer
vendor/bin/ultimate-formatter check     # dry run of both, then PHPStan; stops at the first failure (CI)
```

`check` runs PHPStan only when the project has a `phpstan.neon`, `phpstan.neon.dist` or `phpstan.dist.neon`.
Before anything else it asks PHPStan whether that config loads the [PHPStan ruleset](#phpstan-ruleset),
and fails with the includes to add when it doesn't, since the ruleset's rules would silently never run.
Pass `--without-phpstan-ruleset` to run PHPStan without it.

`format --models` regenerates model docblocks with laravel-ide-helper first, which is installed with the package. In a Laravel app the
same commands are available as `php artisan formatter:install`, `formatter:format` and
`formatter:check`.

Pass `-q` to silence every step, so only the exit code reports the result. Pass `-v`, `-vv` or `-vvv` to
print each command before it runs and hand the same level to it. Both replace any `--quiet` or `-v` in the
configured arguments. Rector has no such flags, so it gets `--debug` at `-vvv` and nothing otherwise.

Record bulk formatting commits in `.git-blame-ignore-revs` so blame skips them, and point
`blame.ignoreRevsFile` at that file in your local git config.

## Customising

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

### Ruleset flags

Each rule of the [PHPStan ruleset](#phpstan-ruleset) has a flag in `config/ultimate-formatter.php`,
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

### Ignoring a line

A `formatter-ignore` comment tells the tools to leave one line alone. Put it at the end of the line, or
on its own line above it, and name each rule it ignores, separated by commas. An optional `-- reason`
follows the list:

```php
$postalCode = 11122; // formatter-ignore UltimateFormatter/numeric_literal_separator

// formatter-ignore ChangeSwitchToMatchRector, ultimateFormatter.disallowSprintf -- kept for the legacy API
switch ($format) {
```

Each tool knows its rules by the name it reports:

| Tool         | Identifier                                                  | Scope    |
|--------------|-------------------------------------------------------------|----------|
| PHP CS Fixer | the rule name, such as `array_syntax`                       | the line |
| PHPStan      | the error identifier, such as `phpat.testServicesAreFinal`  | the line |
| Rector       | the short class name, such as `ChangeSwitchToMatchRector`   | the file |

A PHP CS Fixer rule that moves lines, such as `ordered_imports`, can't leave one line behind, so the
marker skips that rule for the whole file. So does a change that would no longer parse with the marked
line kept. Rector has no hook between a rule and the code it rewrites, so its markers always skip the rule
for the whole file. A PHP CS Fixer rule that isn't enabled, or a Rector rule that doesn't exist, stops the
run with an error, so a typo can't hide. PHPStan doesn't list the identifiers it knows, so a misspelt
PHPStan identifier ignores nothing and the error stays.

### Upgrading

`config/ultimate-formatter.php` is yours once it is published, so each release that changes the default
config ships the change as `config/diffs/{version}.diff`, and `migrate-config` replays the diffs newer than
the `config-version` your file records. It matches each change by its lines and keys rather than by line
number, keeps your values and comments, and prints what it changed. It runs after every `composer install`
and `composer update`, once `artisan package:discover` finishes, so a Laravel app needs no script for it. The
`post-update-cmd` script that `install` adds covers apps that removed `package:discover` from their
`post-autoload-dump`. A config published before 1.12, which has no `config-version`, is dated by the keys it
holds. Run it by hand with `--dry-run` to see the changes first:

```bash
vendor/bin/ultimate-formatter migrate-config --dry-run
```

A default you changed keeps your value. A change whose surrounding lines you edited is skipped and printed,
so you can apply it by hand, and the update still succeeds. A diff can't tell a renamed key from a removed
one, so when a key you set is removed, the command names your value for you to move over. The other
published files call the package's factories and include its ruleset, so they pick up new defaults without
a migration.

The diffs are generated on release: semantic-release runs `.github/scripts/generate-config-diff.sh` with
the version it is about to tag. It sets `config-version` to that version on every release and writes a diff only
when the config changed, and both are committed with the release.

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
  imports, ordered `@param`/`@return`/`@throws` tags, and all traits in one `use` statement, sorted
  alphabetically, at the top of the class body. The custom `UltimateFormatter/single_trait_use_statement`
  fixer merges separate trait statements; a statement with a `{ ... }` conflict resolution block or a
  comment is left alone.
- **Rector.** Type declarations, docblock types, and the Laravel quality, collection and type sets
  matched to the installed Laravel version. Also:
  - constructor property promotion;
  - `switch` to `match`. A `switch` without a `default` becomes a `match` without one, so add the arm
    yourself;
  - closures that return a single-line expression become arrow functions. Closures returning a
    multi-line expression stay closures, and `database/migrations` is always skipped;
  - Carbon instead of `date()`, `time()` and `new DateTime()`, through the `Date` facade so "now" can
    be faked in tests;
  - `dd()`, `dump()`, `var_dump()`, `print_r()` and `ray()` statements are removed;
  - with spatie/laravel-data installed, `XxxInput::validateAndCreate()` becomes `XxxInput::from()`.
- **Left off on purpose.** Rector's `PRIVATIZATION` set would make job properties private, which
  breaks queue serialization, and its `CODING_STYLE` set rewrites interpolated strings to `sprintf()`.
  PHP CS Fixer's `single_trait_insert_per_statement` would split `use A, B;` into one statement per
  trait. Tests fail if any of them gets turned on.
- **Numeric literals.** Decimal literals from 1000 up are grouped by thousands (`1_000`, `10_000`,
  `1_000_000`), and misplaced separators are regrouped (`1000_000` becomes `1_000_000`), via the
  custom `UltimateFormatter/numeric_literal_separator` fixer. Hex, octal, binary and exponent literals
  keep their form, which PHP CS Fixer's own `numeric_literal_separator` would change. IDs and years
  stay plain too: a literal assigned or passed to a name ending in `id` or `ids`, or containing `year`
  (`$userId = 10001`, `drillingYear: 1990`, cases of a `CensusYear` enum). A literal passed as a
  positional argument, like `setISODate(2014, 1)`, has no name to go by and still gets grouped.
- **Docblocks.** Every method gets `@param`/`@return` tags in line with its signature. Narrower tags
  such as `non-empty-string` or `list<string>` are kept. `?string` in a tag becomes `string|null`.
- **Banner comments.** A `/* | Section | */` banner is always followed by a blank line, via the
  custom `UltimateFormatter/blank_line_after_banner_comment` fixer.
- **Laravel style rules.** Custom Rector rules that only rewrite cases where the result is certain:
  - `!$x` becomes `$x === false` for booleans and `$x === null` for nullable objects. Strings, ints
    and mixed values are left alone.
  - Single-line concatenations of strings and plain variable, property or array reads become
    interpolation. Chains that include a call or a constant are left for you to extract a variable.
  - All `use Trait;` statements are merged into one alphabetically ordered statement at the top of
    the class.
  - `array_key_exists($k, $a) ? $a[$k] : $default` becomes `$a[$k] ?? $default`.
  - A `foreach` whose body is two or more nested `if`s without `else` becomes early `continue` guards.
  - A generic foreach value name (`$item`, `$value`, `$row`, ...) becomes the singular of the
    iterated variable (`$users` gives `$user`), unless that name is taken or the old one is used
    outside the loop.
  - String keys in factory `definition()`, `state()`, `make()` and `create()` arrays become the
    model's `ATTRIBUTE_*` constant when exactly one constant has that value.

### PHPStan ruleset

`phpstan-rules.neon` reports what the formatters can't fix safely: banned calls (`sprintf`, the
`array_*` and `preg_*` functions, request and response helpers in services, mass assignment, ...) and
architecture rules (class naming, base classes, final readonly services kept out of the request and
response, migrations kept off models), and custom rules for what neither can express: migration
conventions (literal names Larastan can read, typed closures, `timestamps()` last, `up()` and `down()`,
`foreignId()->constrained()`), camelCase resource keys, `UPPER_SNAKE_CASE` string enums, no `private`
properties on queued classes and no `$with` or `$appends` on models. Each rule has its own identifier
for baselines and `ignoreErrors`. See [docs/phpstan-ruleset.md](docs/phpstan-ruleset.md) for the full list.

## Testing

```bash
composer test
composer analyse
```

## Changelog

Releases are tagged automatically by semantic-release from the conventional commit titles merged into `main`. See [CHANGELOG](CHANGELOG.md) for what changed.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
