<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan;

use AdamczykPiotr\UltimateFormatter\Ignore\IgnoreMarkers;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

/**
 * Ignores an error whose identifier a `// formatter-ignore <identifier>` marker names on the error's line.
 */
class IgnoreMarkerErrors implements IgnoreErrorExtension
{
    /**
     * @var array<string, IgnoreMarkers>
     */
    protected array $markers = [];

    /**
     * @param Error $error
     * @param Node $node
     * @param Scope $scope
     *
     * @return bool
     */
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        $identifier = $error->getIdentifier();
        $line = $error->getLine();
        if ($identifier === null || $line === null) {
            return false;
        }

        // An error inside a trait is reported on the class using it, but its line is the trait's.
        $file = $error->getTraitFilePath() ?? $error->getFilePath();
        $this->markers[$file] ??= IgnoreMarkers::fromCode(is_file($file) ? (string)file_get_contents($file) : '');

        return $this->markers[$file]->ignores($line, $identifier);
    }
}
