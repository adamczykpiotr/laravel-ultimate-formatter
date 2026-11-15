# What it enforces

`format` fixes everything on this page. What can't be fixed safely is reported by the
[PHPStan ruleset](phpstan-ruleset.md) instead.

## PHP CS Fixer

`declare(strict_types=1)`, next-line braces for classes and methods, ordered imports, ordered
`@param`/`@return`/`@throws` tags, and all traits in one `use` statement, sorted alphabetically, at the
top of the class body. The custom `UltimateFormatter/single_trait_use_statement` fixer merges separate
trait statements; a statement with a `{ ... }` conflict resolution block or a comment is left alone.

## Rector

Type declarations, docblock types, and the Laravel quality, collection and type sets matched to the
installed Laravel version. Also:

- constructor property promotion;
- `switch` to `match`. A `switch` without a `default` becomes a `match` without one, so add the arm
  yourself;
- closures that return a single-line expression become arrow functions. Closures returning a
  multi-line expression stay closures, and `database/migrations` is always skipped;
- Carbon instead of `date()`, `time()` and `new DateTime()`, through the `Date` facade so "now" can
  be faked in tests;
- `dd()`, `dump()`, `var_dump()`, `print_r()` and `ray()` statements are removed;
- with spatie/laravel-data installed, `XxxInput::validateAndCreate()` becomes `XxxInput::from()`.

## Left off on purpose

Rector's `PRIVATIZATION` set would make job properties private, which breaks queue serialization, and
its `CODING_STYLE` set rewrites interpolated strings to `sprintf()`. PHP CS Fixer's
`single_trait_insert_per_statement` would split `use A, B;` into one statement per trait. Tests fail if
any of them gets turned on.

## Numeric literals

Decimal literals from 1000 up are grouped by thousands (`1_000`, `10_000`, `1_000_000`), and misplaced
separators are regrouped (`1000_000` becomes `1_000_000`), via the custom
`UltimateFormatter/numeric_literal_separator` fixer. Hex, octal, binary and exponent literals keep their
form, which PHP CS Fixer's own `numeric_literal_separator` would change. IDs and years stay plain too: a
literal assigned or passed to a name ending in `id` or `ids`, or containing `year` (`$userId = 10001`,
`drillingYear: 1990`, cases of a `CensusYear` enum). A literal passed as a positional argument, like
`setISODate(2014, 1)`, has no name to go by and still gets grouped.

## Docblocks

Every method gets `@param`/`@return` tags in line with its signature. Narrower tags such as
`non-empty-string` or `list<string>` are kept. `?string` in a tag becomes `string|null`.

## Banner comments

A `/* | Section | */` banner is always followed by a blank line, via the custom
`UltimateFormatter/blank_line_after_banner_comment` fixer.

## Laravel style rules

Custom Rector rules that only rewrite cases where the result is certain:

- `!$x` becomes `$x === false` for booleans and `$x === null` for nullable objects. Strings, ints and
  mixed values are left alone.
- Single-line concatenations of strings and plain variable, property or array reads become
  interpolation. Chains that include a call or a constant are left for you to extract a variable.
- All `use Trait;` statements are merged into one alphabetically ordered statement at the top of the
  class.
- `array_key_exists($k, $a) ? $a[$k] : $default` becomes `$a[$k] ?? $default`.
- A `foreach` whose body is two or more nested `if`s without `else` becomes early `continue` guards.
- A generic foreach value name (`$item`, `$value`, `$row`, ...) becomes the singular of the iterated
  variable (`$users` gives `$user`), unless that name is taken or the old one is used outside the loop.
- String keys in factory `definition()`, `state()`, `make()` and `create()` arrays become the model's
  `ATTRIBUTE_*` constant when exactly one constant has that value.
