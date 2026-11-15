# Upgrading

`config/ultimate-formatter.php` is yours once it is published, so new defaults can't simply overwrite
it. Instead, each release that changes the default config ships the change as
`config/diffs/{version}.diff`, and `migrate-config` replays the diffs newer than the `config-version`
your file records.

The other published files call the package's factories and include its ruleset, so they pick up new
defaults without a migration.

## What happens automatically

`migrate-config` runs after every `composer install` and `composer update`, once
`artisan package:discover` finishes, so a Laravel app needs no script for it. The `post-update-cmd`
script that `install` adds covers apps that removed `package:discover` from their `post-autoload-dump`.

It matches each change by its lines and keys rather than by line number, keeps your values and comments,
and prints what it changed. A config published before 1.12, which has no `config-version`, is dated by
the keys it holds.

## Running it by hand

Add `--dry-run` to see the changes without writing them:

```bash
vendor/bin/ultimate-formatter migrate-config --dry-run
```

## When a change is skipped

- **A default you changed** keeps your value.
- **A change whose surrounding lines you edited** is skipped and printed, so you can apply it by hand.
  The update still succeeds.
- **A key you set that was removed** is named along with your value, so you can move it over. A diff
  can't tell a renamed key from a removed one.
