<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

use UnexpectedValueException;

class ConfigReader
{
    public const DEFAULT_CONFIG_FILE = __DIR__ . '/../../config/ultimate-formatter.php';
    public const PUBLISHED_CONFIG_FILE = 'config/ultimate-formatter.php';

    /**
     * @param string $projectPath
     *
     * @return string
     */
    public function publishedConfigFile(string $projectPath): string
    {
        $publishedConfigFile = self::PUBLISHED_CONFIG_FILE;

        return "{$projectPath}/{$publishedConfigFile}";
    }

    /**
     * @param string $projectPath
     * @param string $key
     *
     * @return array<int, string>
     */
    public function arguments(string $projectPath, string $key): array
    {
        $arguments = $this->value($projectPath, $key);
        if (is_array($arguments) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.{$key} must be a list of arguments.");
        }

        return collect($arguments)
            ->map(fn(mixed $argument): string => is_string($argument)
                ? $argument
                : throw new UnexpectedValueException("Config ultimate-formatter.{$key} must contain only strings."))
            ->values()
            ->all();
    }

    /**
     * @param string $projectPath
     * @param string $key
     *
     * @return bool
     */
    public function flag(string $projectPath, string $key): bool
    {
        $flag = $this->value($projectPath, $key);
        if (is_bool($flag) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.{$key} must be a boolean.");
        }

        return $flag;
    }

    /**
     * @param string $projectPath
     * @param string $key
     *
     * @return mixed
     */
    public function value(string $projectPath, string $key): mixed
    {
        $publishedConfigFile = $this->publishedConfigFile($projectPath);

        return $this->publishedValue($publishedConfigFile, $key) ?? $this->defaultValue($key);
    }

    /**
     * @param string $configFile
     * @param string $key
     *
     * @return mixed
     */
    public function publishedValue(string $configFile, string $key): mixed
    {
        // The binary and PHPStan both run without booting Laravel, so the config file is read directly.
        if (is_file($configFile) === false) {
            return null;
        }

        $config = require $configFile;

        return data_get($config, $key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function defaultValue(string $key): mixed
    {
        return $this->publishedValue(self::DEFAULT_CONFIG_FILE, $key);
    }
}
