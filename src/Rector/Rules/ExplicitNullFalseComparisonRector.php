<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PHPStan\Type\TypeCombinator;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ExplicitNullFalseComparisonRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [BooleanNot::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof BooleanNot === false) {
            return null;
        }

        $comparedTo = $this->resolveComparedConstant($node->expr);
        if ($comparedTo === null) {
            return null;
        }

        return new Identical($node->expr, new ConstFetch(new Name($comparedTo)));
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Compare explicitly against `null` or `false` instead of negating a nullable object or a bool',
            [
                new CodeSample(
                    <<<'BEFORE'
public function run(?User $user, Tag $tag): void
{
    if (!$user || !$tag->isLocked()) {
        return;
    }
}
BEFORE,
                    <<<'AFTER'
public function run(?User $user, Tag $tag): void
{
    if ($user === null || $tag->isLocked() === false) {
        return;
    }
}
AFTER,
                ),
            ],
        );
    }

    /**
     * Only a bool or an object-or-null has one falsy value; a string, int or array is left alone, since
     * its truthiness check covers several values.
     *
     * @param Expr $expr
     *
     * @return 'false'|'null'|null
     */
    protected function resolveComparedConstant(Expr $expr): ?string
    {
        $type = $this->getType($expr);
        if ($type->isBoolean()->yes()) {
            return 'false';
        }

        $withoutNull = TypeCombinator::removeNull($type);
        $isNullableObject = TypeCombinator::containsNull($type) && $withoutNull->isObject()->yes();

        return $isNullableObject ? 'null' : null;
    }
}
