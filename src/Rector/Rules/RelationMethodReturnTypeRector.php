<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use AdamczykPiotr\UltimateFormatter\Rector\Support\SubclassIndex;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * rector-laravel's `AddGenericReturnTypeToRelationsRector` only adds generics to relation methods that
 * already declare a return type. This rule declares it, so the generics follow in the same run.
 */
final class RelationMethodReturnTypeRector extends AbstractRector
{
    protected const MODEL_CLASS = 'Illuminate\Database\Eloquent\Model';
    protected const RELATION_CLASS = 'Illuminate\Database\Eloquent\Relations\Relation';

    /**
     * @param ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard
     * @param ReflectionProvider $reflectionProvider
     * @param SubclassIndex $subclassIndex
     */
    public function __construct(
        protected readonly ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard,
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly SubclassIndex $subclassIndex,
    ) {}

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof ClassMethod === false || $node->returnType !== null || $node->isStatic() || $node->isMagic()) {
            return null;
        }

        $stmts = $node->stmts ?? [];
        if (count($stmts) !== 1 || $stmts[0] instanceof Return_ === false || $stmts[0]->expr === null) {
            return null;
        }

        $scope = ScopeFetcher::fetch($node);
        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection === false || $classReflection->is(self::MODEL_CLASS) === false) {
            return null;
        }

        $relationClass = $this->resolveRelationClass($stmts[0]);
        if ($relationClass === null) {
            return null;
        }

        if ($this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($node, $scope)) {
            return null;
        }

        if ($this->isOverriddenByChild($classReflection, $this->getName($node), $scope->getFile())) {
            return null;
        }

        $node->returnType = new FullyQualified($relationClass);

        return $node;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add the relation return type to Eloquent model methods that return a relation',
            [
                new CodeSample(
                    <<<'BEFORE'
public function part()
{
    return $this->belongsTo(Part::class);
}
BEFORE,
                    <<<'AFTER'
public function part(): BelongsTo
{
    return $this->belongsTo(Part::class);
}
AFTER,
                ),
            ],
        );
    }

    /**
     * A child override must declare a compatible return type, which an untyped or differently typed one doesn't.
     *
     * @param ClassReflection $classReflection
     * @param string $methodName
     * @param string $filePath
     *
     * @return bool
     */
    protected function isOverriddenByChild(ClassReflection $classReflection, string $methodName, string $filePath): bool
    {
        foreach ($this->subclassIndex->descendantsOf($classReflection, $filePath) as $descendant) {
            $declaringClass = $descendant->getNativeReflection()->getMethod($methodName)->getDeclaringClass()->getName();
            if ($declaringClass !== $classReflection->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Return_ $return
     *
     * @return string|null
     */
    protected function resolveRelationClass(Return_ $return): ?string
    {
        if ($return->expr === null) {
            return null;
        }

        $classNames = $this->getType($return->expr)->getObjectClassNames();
        if (count($classNames) !== 1 || $this->reflectionProvider->hasClass($classNames[0]) === false) {
            return null;
        }

        $relationClass = $this->reflectionProvider->getClass($classNames[0]);
        if ($relationClass->is(self::RELATION_CLASS) === false || $relationClass->isAnonymous()) {
            return null;
        }

        return $relationClass->getName();
    }
}
