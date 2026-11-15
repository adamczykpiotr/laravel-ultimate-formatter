<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use Illuminate\Support\Str;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenameGenericForeachValueVariableRector extends AbstractRector
{
    protected const GENERIC_NAMES = [
        'el', 'elem', 'element', 'entry', 'item', 'obj', 'object', 'one', 'record', 'row', 'v', 'val', 'value', 'x',
    ];
    protected const DYNAMIC_SCOPE_FUNCTIONS = ['compact', 'extract', 'get_defined_vars', 'parse_str'];

    /**
     * @param BetterNodeFinder $betterNodeFinder
     */
    public function __construct(
        protected readonly BetterNodeFinder $betterNodeFinder,
    ) {}

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Closure::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof FunctionLike === false || $node->getStmts() === null || $this->usesDynamicScope($node)) {
            return null;
        }

        $hasChanged = false;
        foreach ($this->betterNodeFinder->findInstancesOfInFunctionLikeScoped($node, Foreach_::class) as $foreach) {
            $hasChanged = $this->renameValueVariable($node, $foreach) || $hasChanged;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rename a generic foreach value variable to the singular of the iterated variable',
            [
                new CodeSample(
                    <<<'BEFORE'
foreach ($vehicles as $item) {
    $item->park();
}
BEFORE,
                    <<<'AFTER'
foreach ($vehicles as $vehicle) {
    $vehicle->park();
}
AFTER,
                ),
            ],
        );
    }

    /**
     * @param FunctionLike $functionLike
     * @param Foreach_ $foreach
     *
     * @return bool
     */
    protected function renameValueVariable(FunctionLike $functionLike, Foreach_ $foreach): bool
    {
        $currentName = $foreach->valueVar instanceof Variable ? $this->getName($foreach->valueVar) : null;
        $newName = $this->resolveSingularName($foreach->expr);
        if ($currentName === null || $newName === null || in_array($currentName, self::GENERIC_NAMES, true) === false) {
            return false;
        }

        $isSafe = $this->isNameUnused($functionLike, $newName)
            && $this->isOnlyReadInsideLoops($functionLike, $currentName)
            && $this->isNameRebindable($foreach, $currentName);
        if ($isSafe === false) {
            return false;
        }

        $this->traverseNodesWithCallable($foreach, function (Node $node) use ($currentName, $newName): ?Node {
            if ($node instanceof Variable && $this->isName($node, $currentName)) {
                $node->name = $newName;

                return $node;
            }

            return null;
        });

        return true;
    }

    /**
     * @param Expr $iterated
     *
     * @return string|null
     */
    protected function resolveSingularName(Expr $iterated): ?string
    {
        $pluralName = match (true) {
            $iterated instanceof Variable => $this->getName($iterated),
            $iterated instanceof PropertyFetch && $iterated->name instanceof Identifier => $iterated->name->toString(),
            default => null,
        };
        if ($pluralName === null) {
            return null;
        }

        $singularName = Str::singular($pluralName);
        $isUsable = $singularName !== $pluralName
            && $singularName !== 'this'
            && in_array($singularName, self::GENERIC_NAMES, true) === false
            && Str::isMatch('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $singularName);

        return $isUsable ? $singularName : null;
    }

    /**
     * @param FunctionLike $functionLike
     * @param string $name
     *
     * @return bool
     */
    protected function isNameUnused(FunctionLike $functionLike, string $name): bool
    {
        $variable = $this->betterNodeFinder->findFirst(
            $functionLike,
            fn(Node $node): bool => $node instanceof Variable && $this->isName($node, $name),
        );

        return $variable === null;
    }

    /**
     * @param FunctionLike $functionLike
     * @param string $name
     *
     * @return bool
     */
    protected function isOnlyReadInsideLoops(FunctionLike $functionLike, string $name): bool
    {
        $bindingLoops = collect($this->betterNodeFinder->findInstanceOf($functionLike, Foreach_::class))
            ->filter(fn(Foreach_ $loop): bool => $loop->valueVar instanceof Variable && $this->isName($loop->valueVar, $name));

        $variablesInLoops = $bindingLoops
            ->flatMap(fn(Foreach_ $loop): array => $this->betterNodeFinder->findInstanceOf($loop, Variable::class))
            ->map(fn(Variable $variable): int => spl_object_id($variable));

        $allVariables = collect($this->betterNodeFinder->findInstanceOf($functionLike, Variable::class))
            ->filter(fn(Variable $variable): bool => $this->isName($variable, $name))
            ->map(fn(Variable $variable): int => spl_object_id($variable));

        return $allVariables->diff($variablesInLoops)->isEmpty();
    }

    /**
     * @param Foreach_ $foreach
     * @param string $name
     *
     * @return bool
     */
    protected function isNameRebindable(Foreach_ $foreach, string $name): bool
    {
        $shadowingFunction = $this->betterNodeFinder->findFirst(
            $foreach,
            fn(Node $node): bool => ($node instanceof Closure || $node instanceof ArrowFunction)
                && collect($node->params)->contains(fn(Param $param): bool => $this->isName($param->var, $name)),
        );

        // An inline `@var` tag naming the old variable isn't renamed with it.
        $docMention = $this->betterNodeFinder->findFirst(
            $foreach,
            fn(Node $node): bool => collect($node->getComments())->contains(
                fn(Comment $comment): bool => Str::isMatch("/\\\${$name}\\b/", $comment->getText()),
            ),
        );

        return $shadowingFunction === null && $docMention === null;
    }

    /**
     * @param FunctionLike $functionLike
     *
     * @return bool
     */
    protected function usesDynamicScope(FunctionLike $functionLike): bool
    {
        $dynamicUse = $this->betterNodeFinder->findFirst(
            $functionLike,
            fn(Node $node): bool => ($node instanceof Variable && is_string($node->name) === false)
                || ($node instanceof FuncCall && $this->isNames($node, self::DYNAMIC_SCOPE_FUNCTIONS)),
        );

        return $dynamicUse !== null;
    }
}
