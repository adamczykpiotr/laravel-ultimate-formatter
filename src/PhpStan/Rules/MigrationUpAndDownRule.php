<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use Illuminate\Database\Migrations\Migration;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 */
class MigrationUpAndDownRule implements Rule
{
    protected const REQUIRED_METHODS = ['up', 'down'];

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        if ($class->isAbstract() || $class->is(Migration::class) === false) {
            return [];
        }

        $errors = [];
        foreach (self::REQUIRED_METHODS as $method) {
            if ($class->hasNativeMethod($method) === false) {
                $errors[] = RuleErrorBuilder::message("Migration has no {$method}() method, every migration has both up() and down().")
                    ->identifier('ultimateFormatter.migrationUpAndDown')
                    ->build();
            }
        }

        return $errors;
    }
}
