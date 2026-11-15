<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ArrayKeyExistsTernaryToCoalesceRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Ternary::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Ternary === false || $node->if === null) {
            return null;
        }

        $isNegated = $node->cond instanceof BooleanNot;
        $condition = $node->cond instanceof BooleanNot ? $node->cond->expr : $node->cond;
        $value = $isNegated ? $node->else : $node->if;
        $fallback = $isNegated ? $node->if : $node->else;

        if ($this->isKeyExistsCheckFor($condition, $value) === false) {
            return null;
        }

        return new Coalesce($value, $fallback);
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace an `array_key_exists()` ternary with `??`',
            [
                new CodeSample(
                    <<<'BEFORE'
$city = array_key_exists('city', $address) ? $address['city'] : '';
BEFORE,
                    <<<'AFTER'
$city = $address['city'] ?? '';
AFTER,
                ),
            ],
        );
    }

    /**
     * @param Expr $condition
     * @param Expr $value
     *
     * @return bool
     */
    protected function isKeyExistsCheckFor(Expr $condition, Expr $value): bool
    {
        if ($condition instanceof FuncCall === false || $this->isName($condition, 'array_key_exists') === false) {
            return false;
        }

        if ($value instanceof ArrayDimFetch === false || $value->dim === null || $condition->isFirstClassCallable()) {
            return false;
        }

        $args = $condition->getArgs();
        $hasNamedOrUnpackedArg = collect($args)->contains(fn(Arg $arg): bool => $arg->name !== null || $arg->unpack);
        if (count($args) !== 2 || $hasNamedOrUnpackedArg) {
            return false;
        }

        return $this->nodeComparator->areNodesEqual($args[0]->value, $value->dim)
            && $this->nodeComparator->areNodesEqual($args[1]->value, $value->var);
    }
}
