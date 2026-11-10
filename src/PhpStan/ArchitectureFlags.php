<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

class ArchitectureFlags implements IgnoreErrorExtension
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
        $this->rules = $this->architectureRules($publishedConfigFile) + $this->architectureRules(self::DEFAULT_CONFIG_FILE);
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
        if (str_starts_with($identifier, 'phpat.') === false) {
            return false;
        }

        // The result cache then reanalyses this file when a flag changes.
        if ($scope instanceof DependencyTracker) {
            $scope->trackFileDependency($this->publishedConfigFile);
        }

        return ($this->rules[str_replace('phpat.', '', $identifier)] ?? true) === false;
    }

    /**
     * @param string $configFile
     *
     * @return array<string, bool>
     */
    protected function architectureRules(string $configFile): array
    {
        // PHPStan runs without Laravel, so the config file is read directly.
        $config = is_file($configFile) ? require $configFile : [];
        $rules = is_array($config) && is_array($config['architecture'] ?? null) ? $config['architecture'] : [];

        $flags = [];
        foreach ($rules as $rule => $enabled) {
            if (is_string($rule) && is_bool($enabled)) {
                $flags[$rule] = $enabled;
            }
        }

        return $flags;
    }
}
