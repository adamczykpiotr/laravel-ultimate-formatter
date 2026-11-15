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
 * Only string-backed enums: int-backed ones are allowed when the numbers matter, and External{Name} enums mirror
 * values from outside the app.
 *
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
        $enum = $scope->getClassReflection();
        if ($enum === null || $enum->getBackedEnumType()?->isString()->yes() !== true) {
            return [];
        }

        if (Str::startsWith(Str::afterLast($enum->getName(), '\\'), 'External')) {
            return [];
        }

        $case = $node->name->toString();
        $errors = [];

        if ($this->isScreamingCase($case) === false) {
            $errors[] = RuleErrorBuilder::message("Enum case {$case} must be UPPER_SNAKE_CASE.")
                ->identifier('ultimateFormatter.enumCasesUpperSnakeCase')
                ->build();
        }

        if ($node->expr instanceof String_ && ($node->expr->value !== $case || $this->isScreamingCase($node->expr->value) === false)) {
            $errors[] = RuleErrorBuilder::message("Enum case {$case} is backed by '{$node->expr->value}', the backing value must be UPPER_SNAKE_CASE and equal the case name. Put any other representation in a method.")
                ->identifier('ultimateFormatter.enumValuesMatchCaseNames')
                ->build();
        }

        return $errors;
    }

    /**
     * Case names only hold letters, digits and underscores, so no lowercase letter means UPPER_SNAKE_CASE.
     *
     * @param string $value
     *
     * @return bool
     */
    protected function isScreamingCase(string $value): bool
    {
        return $value !== '' && $value === Str::upper($value);
    }
}
