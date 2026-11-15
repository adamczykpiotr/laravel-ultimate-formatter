<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\EnumCase;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<EnumCase>
 */
class EnumCaseRule implements Rule
{
    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return EnumCase::class;
    }

    /**
     * @param EnumCase $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $case = $node->name->toString();
        $errors = [];

        if (Str::isMatch('/^[A-Z][A-Z0-9]*(_[A-Z0-9]+)*$/', $case) === false) {
            $errors[] = RuleErrorBuilder::message("Enum case {$case} must be UPPER_SNAKE_CASE.")
                ->identifier('ultimateFormatter.enumCasesUpperSnakeCase')
                ->build();
        }

        // Int-backed enums are allowed when the numbers matter, so only string values must repeat the name.
        if ($node->expr instanceof String_ && $node->expr->value !== $case) {
            $errors[] = RuleErrorBuilder::message("Enum case {$case} is backed by '{$node->expr->value}', the backing value must equal the case name. Put any other representation in a method.")
                ->identifier('ultimateFormatter.enumValuesMatchCaseNames')
                ->build();
        }

        return $errors;
    }
}
