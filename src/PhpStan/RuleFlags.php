<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use UnexpectedValueException;

class RuleFlags implements IgnoreErrorExtension
{
    protected const DEFAULT_CONFIG_FILE = __DIR__ . '/../../config/ultimate-formatter.php';

    /**
     * @var array<string, bool>
     */
    protected array $rules;

    /**
     * @param string $publishedConfigFile
     */
    public function __construct(
        protected readonly string $publishedConfigFile,
    ) {
        // Read on every run, not baked into PHPStan's cached container, so flipping a flag takes effect at once.
        $defaults = $this->flags(self::DEFAULT_CONFIG_FILE);
        $published = $this->flags($publishedConfigFile);

        // Only the ruleset's own rules can be turned off; any other error is left to ignoreErrors. A typo or a
        // renamed identifier would otherwise leave its rule on without a word, so it stops the run instead.
        $unknown = array_keys(array_diff_key($published, $defaults));
        if ($unknown !== []) {
            $rules = implode(', ', $unknown);

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
     * @param string $configFile
     *
     * @return array<string, bool>
     */
    protected function flags(string $configFile): array
    {
        // PHPStan runs without Laravel, so the config file is read directly.
        $config = is_file($configFile) ? require $configFile : [];
        $rules = is_array($config) ? $config['phpstan-rules'] ?? [] : [];
        if (is_array($rules) === false) {
            throw new UnexpectedValueException("Config ultimate-formatter.phpstan-rules in {$configFile} must map rule identifiers to booleans.");
        }

        $flags = [];
        foreach ($rules as $rule => $enabled) {
            if (is_string($rule) === false || is_bool($enabled) === false) {
                throw new UnexpectedValueException("Config ultimate-formatter.phpstan-rules.{$rule} in {$configFile} must be true or false.");
            }

            $flags[$rule] = $enabled;
        }

        return $flags;
    }
}
