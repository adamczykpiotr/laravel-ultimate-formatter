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
     *
     * @return FormatterConfig
     */
    public function forProject(string $projectPath): FormatterConfig
    {
        return $this->forConfigFile($this->publishedConfigFile($projectPath));
    }

    /**
     * @param string $publishedConfigFile
     *
     * @return FormatterConfig
     */
    public function forConfigFile(string $publishedConfigFile): FormatterConfig
    {
        return new FormatterConfig($publishedConfigFile, $this->load($publishedConfigFile), $this->load(self::DEFAULT_CONFIG_FILE));
    }

    /**
     * The binary and PHPStan both run without booting Laravel, so the config file is read directly.
     *
     * @param string $configFile
     *
     * @return array<array-key, mixed>
     */
    protected function load(string $configFile): array
    {
        if (is_file($configFile) === false) {
            return [];
        }

        $config = require $configFile;
        if (is_array($config) === false) {
            throw new UnexpectedValueException("{$configFile} must return an array.");
        }

        return $config;
    }
}
