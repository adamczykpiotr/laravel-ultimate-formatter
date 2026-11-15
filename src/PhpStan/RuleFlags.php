<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use AdamczykPiotr\UltimateFormatter\Config\ConfigReader;
use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

class RuleFlags implements IgnoreErrorExtension
{
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
        $this->rules = $configReader->forConfigFile($publishedConfigFile)->phpstanRules();
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
}
