<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
class ModelWithAndAppendsRule implements Rule
{
    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @param ClassPropertyNode $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isStatic() || $node->getClassReflection()->is(Model::class) === false) {
            return [];
        }

        $error = match ($node->getName()) {
            'with' => RuleErrorBuilder::message('Model declares $with, eager-load relations per query with with() instead, so each call site shows what it fetches.')
                ->identifier('ultimateFormatter.modelNoWith'),
            'appends' => RuleErrorBuilder::message('Model declares $appends, shape the output in a JsonResource instead.')
                ->identifier('ultimateFormatter.modelNoAppends'),
            default => null,
        };

        return $error === null ? [] : [$error->build()];
    }
}
