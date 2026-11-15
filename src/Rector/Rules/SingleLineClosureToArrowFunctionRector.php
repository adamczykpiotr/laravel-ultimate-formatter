<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Php74\NodeAnalyzer\ClosureArrowFunctionAnalyzer;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Rector's ClosureToArrowFunctionRector, limited to closures whose returned expression fits on one
 * line. A multi-line expression would be squashed into one long arrow function, where naming the
 * intermediate values reads better.
 */
final class SingleLineClosureToArrowFunctionRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @param ClosureArrowFunctionAnalyzer $closureArrowFunctionAnalyzer
     */
    public function __construct(
        protected readonly ClosureArrowFunctionAnalyzer $closureArrowFunctionAnalyzer,
    ) {}

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Closure::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Closure === false) {
            return null;
        }

        $returnExpr = $this->closureArrowFunctionAnalyzer->matchArrowFunctionExpr($node);
        if ($returnExpr instanceof Expr === false) {
            return null;
        }

        if ($node->getAttribute(AttributeKey::IS_CLOSURE_IN_ATTRIBUTE) === true) {
            return null;
        }

        if ($returnExpr->getStartLine() !== $returnExpr->getEndLine()) {
            return null;
        }

        $attributes = $node->getAttributes();
        unset($attributes[AttributeKey::ORIGINAL_NODE]);

        $arrowFunction = new ArrowFunction([
            'static' => $node->static,
            'byRef' => $node->byRef,
            'params' => $node->params,
            'returnType' => $node->returnType,
            'expr' => $returnExpr,
        ], $attributes);

        $returnStmt = $node->stmts[0];
        if ($returnStmt->getComments() !== []) {
            $this->mirrorComments($arrowFunction->expr, $returnStmt);
            $arrowFunction->setAttribute(AttributeKey::COMMENTS, $returnStmt->getComments());
        }

        return $arrowFunction;
    }

    /**
     * @return int
     */
    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_74;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change a closure that returns a single-line expression to an arrow function',
            [
                new CodeSample(
                    <<<'BEFORE'
$names = $users->map(function (User $user) {
    return $user->name;
});
BEFORE,
                    <<<'AFTER'
$names = $users->map(fn(User $user) => $user->name);
AFTER,
                ),
            ],
        );
    }
}
