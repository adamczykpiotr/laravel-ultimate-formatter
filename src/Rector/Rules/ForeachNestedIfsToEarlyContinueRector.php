<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Replaces the deprecated upstream ChangeNestedForeachIfsToEarlyContinueRector. Only a loop body made of
 * nothing but at least two nested, else-less ifs is flattened, so no code before or after an if changes
 * whether it runs.
 */
final class ForeachNestedIfsToEarlyContinueRector extends AbstractRector
{
    protected const INVERSE_COMPARISONS = [
        Identical::class => NotIdentical::class,
        NotIdentical::class => Identical::class,
        Equal::class => NotEqual::class,
        NotEqual::class => Equal::class,
        Greater::class => SmallerOrEqual::class,
        SmallerOrEqual::class => Greater::class,
        Smaller::class => GreaterOrEqual::class,
        GreaterOrEqual::class => Smaller::class,
    ];

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Foreach_::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Foreach_ === false) {
            return null;
        }

        $guards = [];
        $body = $node->stmts;
        while (count($body) === 1 && $this->isPlainIf($body[0])) {
            /** @var If_ $if */
            $if = $body[0];
            $guards[] = new If_($this->negate($if->cond), ['stmts' => [new Continue_()]], $if->getAttributes());
            $body = $if->stmts;
        }

        if (count($guards) < 2) {
            return null;
        }

        $node->stmts = [...$guards, ...$body];

        return $node;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flatten nested ifs that wrap a whole foreach body into early continue guards',
            [
                new CodeSample(
                    <<<'BEFORE'
foreach ($readings as $reading) {
    if ($reading->value !== null) {
        if ($reading->isValid()) {
            $valid[] = $reading;
        }
    }
}
BEFORE,
                    <<<'AFTER'
foreach ($readings as $reading) {
    if ($reading->value === null) {
        continue;
    }
    if ($reading->isValid() === false) {
        continue;
    }
    $valid[] = $reading;
}
AFTER,
                ),
            ],
        );
    }

    /**
     * @param Stmt $stmt
     *
     * @return bool
     */
    protected function isPlainIf(Stmt $stmt): bool
    {
        return $stmt instanceof If_ && $stmt->else === null && $stmt->elseifs === [];
    }

    /**
     * @param Expr $condition
     *
     * @return Expr
     */
    protected function negate(Expr $condition): Expr
    {
        if ($condition instanceof BooleanNot) {
            return $condition->expr;
        }

        $inverseClass = self::INVERSE_COMPARISONS[$condition::class] ?? null;
        if ($condition instanceof BinaryOp && $inverseClass !== null) {
            return new $inverseClass($condition->left, $condition->right);
        }

        if ($condition instanceof Instanceof_ || $this->getType($condition)->isBoolean()->yes()) {
            return new Identical($condition, new ConstFetch(new Name('false')));
        }

        return new BooleanNot($condition);
    }
}
