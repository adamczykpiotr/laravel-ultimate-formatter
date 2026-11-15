<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use AdamczykPiotr\UltimateFormatter\PhpStan\Rules\Support\MigrationSchema;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
class MigrationForeignIdRule implements Rule
{
    protected const UNSIGNED_INTEGER_METHODS = ['unsignedBigInteger', 'unsignedInteger'];

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = MigrationSchema::methodName($node);
        if ($method === null || in_array($method, self::UNSIGNED_INTEGER_METHODS, true) === false || $node->isFirstClassCallable()) {
            return [];
        }

        $column = MigrationSchema::argument($node, 0, 'column')?->value;
        if ($column instanceof String_ === false || Str::endsWith($column->value, '_id') === false) {
            return [];
        }

        if (MigrationSchema::isInMigration($scope) === false || MigrationSchema::isBlueprintCall($node, $scope) === false) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Foreign key {$column->value} is a bare {$method}(), use foreignId('{$column->value}')->constrained() so the database keeps it from pointing at a missing row.")
                ->identifier('ultimateFormatter.migrationForeignIdConstrained')
                ->build(),
        ];
    }
}
