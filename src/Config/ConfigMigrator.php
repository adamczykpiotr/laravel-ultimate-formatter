<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

/**
 * Brings a published config/ultimate-formatter.php up to date with the package. The published file records the last
 * migration applied to it under config-version, so each migration runs once. A change is checked against the file
 * before it applies, so a file the project already edited by hand is left as it is.
 */
class ConfigMigrator
{
    public const VERSION_KEY = 'config-version';

    /**
     * @var array<int, list<ConfigChange>>
     */
    protected readonly array $migrations;

    /**
     * @param array<int, list<ConfigChange>>|null $migrations
     */
    public function __construct(array|null $migrations = null)
    {
        $this->migrations = $migrations ?? self::migrations();
    }

    /**
     * The changes to config/ultimate-formatter.php, by version, starting at 1. A release that adds, removes or renames
     * a key adds the next version here and sets config-version in config/ultimate-formatter.php to it, for example:
     *
     *     1 => [
     *         ConfigChange::added(['phpstan-rules', 'ultimateFormatter.disallowDd'], true, after: 'ultimateFormatter.disallowSprintf'),
     *         ConfigChange::renamed(['phpstan-rules', 'ultimateFormatter.disallowEnv'], 'ultimateFormatter.disallowEnvCalls'),
     *         ConfigChange::removed(['format', 'ide-helper']),
     *     ],
     *
     * @return array<int, list<ConfigChange>>
     */
    public static function migrations(): array
    {
        return [];
    }

    /**
     * @return int
     */
    public function latestVersion(): int
    {
        return $this->migrations === [] ? 0 : max(array_keys($this->migrations));
    }

    /**
     * @param string $configFile
     * @param bool $dryRun
     *
     * @return ConfigMigration
     */
    public function migrate(string $configFile, bool $dryRun = false): ConfigMigration
    {
        $filesystem = new Filesystem();
        $config = PhpConfigFile::parse($filesystem->readFile($configFile));
        $fromVersion = $config->intValue([self::VERSION_KEY]) ?? 0;
        $toVersion = $this->latestVersion();
        if ($fromVersion >= $toVersion) {
            return new ConfigMigration($fromVersion, $fromVersion);
        }

        $applied = [];
        $skipped = [];
        $pendingChanges = collect($this->migrations)
            ->filter(fn(array $changes, int $version): bool => $version > $fromVersion)
            ->sortKeys()
            ->flatMap(fn(array $changes): array => $changes);
        foreach ($pendingChanges as $change) {
            try {
                [$config, $message] = $this->apply($config, $change);
            } catch (UnexpectedValueException $exception) {
                $skipped[] = 'Skipped ' . $this->describe($change) . ", {$exception->getMessage()}.";

                continue;
            }

            if ($message !== null) {
                $applied[] = $message;
            }
        }

        // A skipped change is reported once, then left to the project, so the version moves on either way.
        $config = $config->entry([self::VERSION_KEY]) === null
            ? $config->add([], self::VERSION_KEY, $toVersion)
            : $config->set([self::VERSION_KEY], $toVersion);

        if ($dryRun === false) {
            $filesystem->dumpFile($configFile, $config->contents());
        }

        return new ConfigMigration($fromVersion, $toVersion, $applied, $skipped);
    }

    /**
     * A change the file already reflects, such as removing a key that is gone, does nothing.
     *
     * @param PhpConfigFile $config
     * @param ConfigChange $change
     *
     * @return array{PhpConfigFile, string|null}
     */
    protected function apply(PhpConfigFile $config, ConfigChange $change): array
    {
        $key = implode('.', $change->path);

        return match ($change->type) {
            ConfigChangeType::ADDED => $this->add($config, $change),
            ConfigChangeType::REMOVED => $config->entry($change->path) === null
                ? [$config, null]
                : [$config->remove($change->path), "Removed {$key}."],
            ConfigChangeType::RENAMED => $this->rename($config, $change),
        };
    }

    /**
     * @param PhpConfigFile $config
     * @param ConfigChange $change
     *
     * @return array{PhpConfigFile, string|null}
     */
    protected function add(PhpConfigFile $config, ConfigChange $change): array
    {
        // Without its array the whole section falls back to the package defaults, the new key included.
        $parent = $config->array($change->parentPath());
        if ($parent === null || isset($parent->entries[$change->key()])) {
            return [$config, null];
        }

        $config = $config->add($change->parentPath(), $change->key(), $change->value, $change->after);

        return [$config, 'Added ' . implode('.', $change->path) . '.'];
    }

    /**
     * @param PhpConfigFile $config
     * @param ConfigChange $change
     *
     * @return array{PhpConfigFile, string|null}
     */
    protected function rename(PhpConfigFile $config, ConfigChange $change): array
    {
        if ($config->entry($change->path) === null) {
            return [$config, null];
        }

        $oldKey = implode('.', $change->path);
        $newKey = implode('.', $change->newPath());
        // The project already set the new key, so its value wins over the old one.
        if ($config->entry($change->newPath()) !== null) {
            return [$config->remove($change->path), "Removed {$oldKey}, {$newKey} already exists."];
        }

        return [$config->rename($change->path, (string)$change->newKey), "Renamed {$oldKey} to {$newKey}."];
    }

    /**
     * @param ConfigChange $change
     *
     * @return string
     */
    protected function describe(ConfigChange $change): string
    {
        $key = implode('.', $change->path);

        return match ($change->type) {
            ConfigChangeType::ADDED => "adding {$key}",
            ConfigChangeType::REMOVED => "removing {$key}",
            ConfigChangeType::RENAMED => "renaming {$key} to " . implode('.', $change->newPath()),
        };
    }
}
