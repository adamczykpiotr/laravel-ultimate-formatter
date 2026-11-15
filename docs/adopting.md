# Adopting it in an existing project

The first run on an existing codebase rewrites a lot of files and can report many PHPStan errors. Do it
in this order so the formatting lands as one reviewable commit that blame can skip, and PHPStan doesn't
block CI on day one.

## 1. Install

```bash
composer require --dev adamczykpiotr/laravel-ultimate-formatter
vendor/bin/ultimate-formatter install --ci
```

Leave out `--ci` if you don't use GitHub Actions. If the project already has a `phpstan.neon.dist` or
`phpstan.dist.neon`, add the ruleset to it by hand, as [Setup](setup.md#phpstan) describes.

## 2. Format everything

```bash
vendor/bin/ultimate-formatter format --models
```

Rector changes code, not only whitespace, so run your test suite before you commit. Commit the result
on its own, with no other changes in it.

## 3. Hide the formatting commit from blame

Once that commit is on your main branch, add its SHA to `.git-blame-ignore-revs`. Use the SHA from the
main branch, since squash merges rewrite it. Then point git at the file:

```bash
git config blame.ignoreRevsFile .git-blame-ignore-revs
```

Each developer runs this once in their clone. GitHub reads the file on its own.

## 4. Get PHPStan to pass

```bash
vendor/bin/ultimate-formatter check
```

You have two options for the errors it reports:

- **Turn a rule off** until you're ready to fix it, with its [ruleset flag](configuration.md#ruleset-flags).
  This suits rules that fire across the whole codebase.
- **Baseline the rest**, so only new code has to pass:

  ```bash
  vendor/bin/phpstan analyse --generate-baseline
  ```

  Then add `phpstan-baseline.neon` to the `includes` in `phpstan.neon`.

If Larastan was upgraded along with the package, some errors come from Larastan rather than the ruleset.
[Setup](setup.md#phpstan) explains this. Baseline them the same way.

## 5. Keep it that way

Run `format` before you commit, and let CI run `check`. When you upgrade the package,
[`migrate-config`](upgrading.md) brings your published config up to date.
