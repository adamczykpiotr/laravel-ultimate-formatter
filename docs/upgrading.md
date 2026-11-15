# Upgrading

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

## How the diffs are made

The diffs are generated on release: semantic-release runs `.github/scripts/generate-config-diff.sh` with
the version it is about to tag. It sets `config-version` to that version on every release and writes a diff only
when the config changed, and both are committed with the release.
