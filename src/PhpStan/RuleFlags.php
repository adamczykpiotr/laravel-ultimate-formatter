<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use UnexpectedValueException;

class RuleFlags implements IgnoreErrorExtension
{
    protected const FLAGS_KEY = 'phpstan-rules';

    /**
     * @var array<string, bool>
     */
    protected array $rules;

    /**
     * @param string $publishedConfigFile
     * @param ConfigReader $configReader
     */
    public function __construct(
        protected readonly string $publishedConfigFile,
        protected readonly ConfigReader $configReader = new ConfigReader(),
    ) {
        // Read on every run, not baked into PHPStan's cached container, so flipping a flag takes effect at once.
        $defaults = $this->flags($configReader->defaultSection(self::FLAGS_KEY), ConfigReader::DEFAULT_CONFIG_FILE);
        $published = $this->flags($configReader->publishedSection($publishedConfigFile, self::FLAGS_KEY), $publishedConfigFile);

        // Only the ruleset's own rules can be turned off; any other error is left to ignoreErrors. A typo or a
        // renamed identifier would otherwise leave its rule on without a word, so it stops the run instead.
        $unknown = collect($published)->diffKeys($defaults)->keys();
        if ($unknown->isNotEmpty()) {
            $rules = $unknown->implode(', ');

            throw new UnexpectedValueException("Config ultimate-formatter.phpstan-rules in {$publishedConfigFile} has unknown rules: {$rules}. Use the identifiers listed in docs/phpstan-ruleset.md.");
        }

        $this->rules = $published + $defaults;
    }

    /**
     * @param Error $error
     * @param Node $node
     * @param Scope $scope
     *
     * @return bool
     */
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        $identifier = (string)$error->getIdentifier();
        if (isset($this->rules[$identifier]) === false) {
            return false;
        }

        // The result cache then reanalyses this file when a flag changes.
        if ($scope instanceof DependencyTracker) {
            $scope->trackFileDependency($this->publishedConfigFile);
        }

        return $this->rules[$identifier] === false;
    }

    /**
     * @param mixed $rules
     * @param string $configFile
     *
     * @return array<string, bool>
     */
    protected function flags(mixed $rules, string $configFile): array
    {
        $rules ??= [];
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
