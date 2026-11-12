<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

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

        // Only the ruleset's own rules can be turned off; any other error is left to ignoreErrors.
        $this->rules = array_intersect_key($this->flags($publishedConfigFile), $defaults) + $defaults;
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
        $rules = is_array($config) && is_array($config['phpstan-rules'] ?? null) ? $config['phpstan-rules'] : [];

        $flags = [];
        foreach ($rules as $rule => $enabled) {
            if (is_string($rule) && is_bool($enabled)) {
                $flags[$rule] = $enabled;
            }
        }

        return $flags;
    }
}
