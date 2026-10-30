<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use Illuminate\Support\Facades\Log;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * disallowed-calls matches the declaring class, and shouldReceive() is declared on every facade, so Log gets its own rule.
 *
 * @implements Rule<StaticCall>
 */
class NoLogShouldReceiveRule implements Rule
{
    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param StaticCall $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name instanceof Identifier === false || $node->name->toLowerString() !== 'shouldreceive') {
            return [];
        }

        if ($node->class instanceof Name === false) {
            return [];
        }

        $calledOnType = $scope->resolveTypeByName($node->class);
        if ((new ObjectType(Log::class))->isSuperTypeOf($calledOnType)->yes() === false) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Calling Log::shouldReceive() is forbidden, arrange with Log::spy() and assert after the fact instead.')
                ->identifier('ultimateFormatter.logShouldReceive')
                ->build(),
        ];
    }
}
