<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use Illuminate\Contracts\Queue\ShouldQueue;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * SerializesModels reflects over the concrete class, so a private property declared on a parent arrives empty on the
 * worker.
 *
 * @implements Rule<ClassPropertyNode>
 */
class QueuedClassPrivatePropertyRule implements Rule
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
        if ($node->isPrivate() === false || $node->getClassReflection()->is(ShouldQueue::class) === false) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Property \${$node->getName()} of a queued class must be protected, not private, or it can arrive empty on the worker.")
                ->identifier('ultimateFormatter.queuedClassNoPrivateProperties')
                ->build(),
        ];
    }
}
