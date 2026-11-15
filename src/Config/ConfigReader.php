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
     * @param string $section
     * @param string $key
     *
     * @return array<int, string>
     */
    public function arguments(string $projectPath, string $section, string $key): array
    {
        $arguments = $this->value($projectPath, $section, $key);
        if (is_array($arguments) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.{$section}.{$key} must be a list of arguments.");
        }

        return collect($arguments)
            ->map(fn(mixed $argument): string => is_string($argument)
                ? $argument
                : throw new UnexpectedValueException("Config ultimate-formatter.{$section}.{$key} must contain only strings."))
            ->values()
            ->all();
    }

    /**
     * @param string $projectPath
     * @param string $section
     * @param string $key
     *
     * @return bool
     */
    public function flag(string $projectPath, string $section, string $key): bool
    {
        $flag = $this->value($projectPath, $section, $key);
        if (is_bool($flag) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.{$section}.{$key} must be a boolean.");
        }

        return $flag;
    }

    /**
     * A key the published config leaves out keeps its default, so an older config still runs every tool.
     *
     * @param string $projectPath
     * @param string $section
     * @param string $key
     *
     * @return mixed
     */
    public function value(string $projectPath, string $section, string $key): mixed
    {
        $publishedSection = $this->publishedSection($this->publishedConfigFile($projectPath), $section);
        $defaultSection = $this->defaultSection($section);

        return $this->entry($publishedSection, $key) ?? $this->entry($defaultSection, $key);
    }

    /**
     * @param string $configFile
     * @param string $section
     *
     * @return mixed
     */
    public function publishedSection(string $configFile, string $section): mixed
    {
        // The binary and PHPStan both run without booting Laravel, so the config file is read directly.
        if (is_file($configFile) === false) {
            return null;
        }

        $config = require $configFile;
        if (is_array($config) === false) {
            throw new UnexpectedValueException("{$configFile} must return an array.");
        }

        return $config[$section] ?? null;
    }

    /**
     * @param string $section
     *
     * @return mixed
     */
    public function defaultSection(string $section): mixed
    {
        return $this->publishedSection(self::DEFAULT_CONFIG_FILE, $section);
    }

    /**
     * @param mixed $section
     * @param string $key
     *
     * @return mixed
     */
    protected function entry(mixed $section, string $key): mixed
    {
        return is_array($section) ? $section[$key] ?? null : null;
    }
}
