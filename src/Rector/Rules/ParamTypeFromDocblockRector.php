<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\PHPStan\ScopeFetcher;
use Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Rector dropped its own docblock-to-native param rule, since a wrong `@param` turns into a `TypeError`.
 * This one only fills in params that have no native type, and skips methods whose signature a parent
 * class or interface fixes, where a narrower param type would be a fatal error.
 */
final class ParamTypeFromDocblockRector extends AbstractRector
{
    /**
     * @param PhpDocInfoFactory $phpDocInfoFactory
     * @param StaticTypeMapper $staticTypeMapper
     */
    public function __construct(
        protected readonly PhpDocInfoFactory $phpDocInfoFactory,
        protected readonly StaticTypeMapper $staticTypeMapper,
    ) {}

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof FunctionLike === false || $node->getDocComment() === null) {
            return null;
        }

        if ($node instanceof ClassMethod && $this->isSignatureLocked($node)) {
            return null;
        }

        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);

        $hasChanged = false;
        foreach ($node->getParams() as $param) {
            // A promoted param is a property as well, and the type would bind every later assignment to it.
            if ($param->type !== null || $param->variadic || $param->isPromoted()) {
                continue;
            }

            $paramName = $this->getName($param->var);
            if ($paramName === null) {
                continue;
            }

            $typeNode = $this->nativeTypeNode($phpDocInfo->getParamType($paramName), $param);
            if ($typeNode === null) {
                continue;
            }

            $param->type = $typeNode;
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a native param type from the @param tag to params that have none',
            [
                new CodeSample(
                    <<<'BEFORE'
/**
 * @param int $odometerKm
 * @return float
 */
private function wearFactor($odometerKm): float
BEFORE,
                    <<<'AFTER'
/**
 * @param int $odometerKm
 * @return float
 */
private function wearFactor(int $odometerKm): float
AFTER,
                ),
            ],
        );
    }

    /**
     * @param Type $docType
     * @param Param $param
     *
     * @return ComplexType|Identifier|Name|null
     */
    protected function nativeTypeNode(Type $docType, Param $param): ComplexType|Identifier|Name|null
    {
        if ($docType instanceof MixedType || $docType->isNull()->yes()) {
            return null;
        }

        // `@param int $limit` with `$limit = null` would need a nullable type the docblock doesn't give.
        if ($param->default !== null && $docType->isSuperTypeOf($this->getType($param->default))->yes() === false) {
            return null;
        }

        $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($docType, TypeKind::PARAM);
        if ($typeNode === null || $this->getName($typeNode) === 'mixed') {
            return null;
        }

        return $typeNode;
    }

    /**
     * A parent class or interface that declares the method fixes its params, and so does a trait,
     * whose methods can override any parent of the class using it. A constructor is fixed only by an
     * interface or an abstract declaration.
     *
     * @param ClassMethod $classMethod
     *
     * @return bool
     */
    protected function isSignatureLocked(ClassMethod $classMethod): bool
    {
        if ($classMethod->isMagic() && $this->isName($classMethod, '__construct') === false) {
            return true;
        }

        $classReflection = ScopeFetcher::fetch($classMethod)->getClassReflection();
        if ($classReflection instanceof ClassReflection === false || $classReflection->isTrait()) {
            return true;
        }

        if ($classMethod->isPrivate()) {
            return false;
        }

        $methodName = $this->getName($classMethod);
        $isConstructor = $methodName === '__construct';
        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->getName() === $classReflection->getName() || $ancestor->hasNativeMethod($methodName) === false) {
                continue;
            }

            // A constructor only has to match one declared by an interface or as abstract.
            if ($isConstructor === false || $this->isAbstractConstructor($ancestor)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassReflection $classReflection
     *
     * @return bool
     */
    protected function isAbstractConstructor(ClassReflection $classReflection): bool
    {
        $nativeReflection = $classReflection->getNativeReflection();

        return $nativeReflection->hasMethod('__construct') && $nativeReflection->getMethod('__construct')->isAbstract();
    }
}
