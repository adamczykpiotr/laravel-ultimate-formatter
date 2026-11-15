# Contributing

## Testing

```bash
composer test
composer analyse
composer check
```

`check` runs the package's own formatter and PHPStan ruleset on itself.

## Releases

Releases are tagged automatically by semantic-release from the conventional commit titles merged into
`main`, and it writes [CHANGELOG.md](CHANGELOG.md). Use a conventional commit title, such as
`fix(check): ...` or `feat: ...`, for every PR.

## Config diffs

[`migrate-config`](docs/upgrading.md) replays the changes to the default config, so a change to
`config/ultimate-formatter.php` needs no migration code. The diffs are generated on release:
semantic-release runs `.github/scripts/generate-config-diff.sh` with the version it is about to tag. It
sets `config-version` to that version on every release and writes `config/diffs/{version}.diff` only when
the config changed, and both are committed with the release.
