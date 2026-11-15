<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config;

use AdamczykPiotr\UltimateFormatter\Console\ArtisanStep;
use AdamczykPiotr\UltimateFormatter\Console\RunMode;
use AdamczykPiotr\UltimateFormatter\Console\Tool;
use UnexpectedValueException;

class FormatterConfig
{
    protected const PRE_SECTION = 'pre';
    protected const PHPSTAN_RULES_SECTION = 'phpstan-rules';

    // docs/ is export-ignored, so the installed package has no copy of the list to point at.
    protected const RULESET_DOCS_URL = 'https://github.com/adamczykpiotr/laravel-ultimate-formatter/blob/main/docs/phpstan-ruleset.md';

    /**
     * @param string $publishedConfigFile
     * @param array<array-key, mixed> $published
     * @param array<array-key, mixed> $defaults
     */
    public function __construct(
        public readonly string $publishedConfigFile,
        protected readonly array $published,
        protected readonly array $defaults,
    ) {}

    /**
     * @param RunMode $mode
     * @param Tool $tool
     *
     * @return array<int, string>
     */
    public function toolArguments(RunMode $mode, Tool $tool): array
    {
        return $this->arguments($mode->configKey(), $tool->configKey());
    }

    /**
     * @param ArtisanStep $step
     *
     * @return array<int, string>
     */
    public function artisanArguments(ArtisanStep $step): array
    {
        return $this->arguments(RunMode::FORMAT->configKey(), $step->configKey());
    }

    /**
     * @param ArtisanStep $step
     *
     * @return bool
     */
    public function runsBeforeFormat(ArtisanStep $step): bool
    {
        $section = self::PRE_SECTION;
        $flag = $this->value($section, $step->configKey());
        if (is_bool($flag) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.{$section}.{$step->configKey()} must be a boolean.");
        }

        return $flag;
    }

    /**
     * Only the ruleset's own rules can be turned off; any other error is left to ignoreErrors. A typo or a renamed
     * identifier would otherwise leave its rule on without a word, so it stops the run instead.
     *
     * @return array<string, bool>
     */
    public function phpstanRules(): array
    {
        $defaults = $this->ruleFlags($this->defaults, ConfigReader::DEFAULT_CONFIG_FILE);
        $published = $this->ruleFlags($this->published, $this->publishedConfigFile);

        $unknown = collect($published)->diffKeys($defaults)->keys();
        if ($unknown->isNotEmpty()) {
            $rules = $unknown->implode(', ');

            throw new UnexpectedValueException("Config ultimate-formatter.phpstan-rules in {$this->publishedConfigFile} has unknown rules: {$rules}. Use the identifiers listed in " . self::RULESET_DOCS_URL . '.');
        }

        return $published + $defaults;
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return array<int, string>
     */
    protected function arguments(string $section, string $key): array
    {
        $arguments = $this->value($section, $key);
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
     * A key the published config leaves out keeps its default, so an older config still runs every tool.
     *
     * @param string $section
     * @param string $key
     *
     * @return mixed
     */
    protected function value(string $section, string $key): mixed
    {
        return $this->entry($this->published, $section, $key) ?? $this->entry($this->defaults, $section, $key);
    }

    /**
     * @param array<array-key, mixed> $config
     * @param string $section
     * @param string $key
     *
     * @return mixed
     */
    protected function entry(array $config, string $section, string $key): mixed
    {
        $values = $config[$section] ?? null;

        return is_array($values) ? $values[$key] ?? null : null;
    }

    /**
     * @param array<array-key, mixed> $config
     * @param string $configFile
     *
     * @return array<string, bool>
     */
    protected function ruleFlags(array $config, string $configFile): array
    {
        $rules = $config[self::PHPSTAN_RULES_SECTION] ?? [];
        if (is_array($rules) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.phpstan-rules in {$configFile} must map rule identifiers to booleans.");
        }

        return collect($rules)
            ->mapWithKeys(fn(mixed $enabled, int|string $rule): array => is_string($rule) && is_bool($enabled)
                ? [$rule => $enabled]
                : throw new UnexpectedValueException("Config ultimate-formatter.phpstan-rules.{$rule} in {$configFile} must be true or false."))
            ->all();
    }
}
