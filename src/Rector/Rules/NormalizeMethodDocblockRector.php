<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Rector\NodeTypeResolver\TypeComparator\TypeComparator;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Rector\StaticTypeMapper\ValueObject\Type\SelfObjectType;
use Rector\StaticTypeMapper\ValueObject\Type\SelfStaticType;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Keeps every method's @param/@return tags in line with its native signature, adding missing ones.
 * Tags narrower than the native type (`@return non-empty-string` on `: string`) are kept,
 * because the docblock carries information the signature cannot.
 */
final class NormalizeMethodDocblockRector extends AbstractRector
{
    /**
     * @param PhpDocInfoFactory $phpDocInfoFactory
     * @param PhpDocTypeChanger $phpDocTypeChanger
     * @param StaticTypeMapper $staticTypeMapper
     * @param TypeComparator $typeComparator
     */
    public function __construct(
        protected readonly PhpDocInfoFactory $phpDocInfoFactory,
        protected readonly PhpDocTypeChanger $phpDocTypeChanger,
        protected readonly StaticTypeMapper $staticTypeMapper,
        protected readonly TypeComparator $typeComparator,
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
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof ClassMethod === false) {
            return null;
        }

        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $paramsChanged = $this->normalizeParamTags($node, $phpDocInfo);
        $returnChanged = $this->normalizeReturnTag($node, $phpDocInfo);

        if ($returnChanged === false && $paramsChanged === false) {
            return null;
        }

        return $node;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Ensure each method docblock @return/@param tag matches the native signature',
            [
                new CodeSample(
                    <<<'BEFORE'
class FormPolicy
{
    /**
     * @return mixed
     */
    public function view(User $user, Form $form): bool
    {
        return $user->company->id === $form->company_id;
    }
}
BEFORE,
                    <<<'AFTER'
class FormPolicy
{
    /**
     * @param User $user
     * @param Form $form
     * @return bool
     */
    public function view(User $user, Form $form): bool
    {
        return $user->company->id === $form->company_id;
    }
}
AFTER,
                ),
            ],
        );
    }

    /**
     * @param ClassMethod $node
     * @param PhpDocInfo $phpDocInfo
     * @return bool
     */
    protected function normalizeReturnTag(ClassMethod $node, PhpDocInfo $phpDocInfo): bool
    {
        if ($node->returnType === null || $node->name->toString() === '__construct') {
            return false;
        }

        $nativeReturn = $this->mapNativeType($node->returnType);
        if ($this->isAtLeastAsNarrow($phpDocInfo->getReturnType(), $nativeReturn)) {
            return false;
        }

        return $this->phpDocTypeChanger->changeReturnType($node, $phpDocInfo, $this->printableType($nativeReturn));
    }

    /**
     * @param ClassMethod $node
     * @param PhpDocInfo $phpDocInfo
     * @return bool
     */
    protected function normalizeParamTags(ClassMethod $node, PhpDocInfo $phpDocInfo): bool
    {
        $changed = false;

        foreach ($node->getParams() as $param) {
            $name = $param->var->name ?? null;
            if (is_string($name) === false || $name === '') {
                continue;
            }

            // An untyped param without a tag still gets `mixed`.
            $nativeParam = $param->type === null
                ? new MixedType()
                : $this->mapNativeType($param->type);

            if ($this->isAtLeastAsNarrow($phpDocInfo->getParamType($name), $nativeParam)) {
                continue;
            }

            if ($this->phpDocTypeChanger->changeParamType($node, $phpDocInfo, $this->printableType($nativeParam), $param, $name)) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param Type $docType
     * @param Type $nativeType
     * @return bool
     */
    protected function isAtLeastAsNarrow(Type $docType, Type $nativeType): bool
    {
        // Any explicit tag on an untyped param is more precise than `mixed`.
        if ($nativeType instanceof MixedType) {
            return $docType instanceof MixedType === false;
        }

        if ($this->typeComparator->isSubtype($docType, $nativeType)) {
            return true;
        }

        // Rector maps `non-empty-string` to a bare accessory type, which `string` only "maybe" contains.
        $refinedType = TypeCombinator::intersect($nativeType, $docType);

        return $refinedType instanceof NeverType === false && $docType->isSuperTypeOf($nativeType)->yes() === false;
    }

    /**
     * @param Node $typeNode
     * @return Type
     */
    protected function mapNativeType(Node $typeNode): Type
    {
        $nativeType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($typeNode);

        // Native `self` maps to a `$this` type; keep it printed as `self`.
        return TypeTraverser::map($nativeType, fn(Type $type, callable $traverse): Type => $type instanceof SelfStaticType
            ? new SelfObjectType($type->getClassName())
            : $traverse($type));
    }

    /**
     * @param Type $type
     * @return Type
     */
    protected function printableType(Type $type): Type
    {
        // The docblock printer writes the class name verbatim, so `self` must be its literal name.
        return TypeTraverser::map($type, fn(Type $type, callable $traverse): Type => $type instanceof SelfObjectType
            ? new SelfObjectType('self')
            : $traverse($type));
    }
}
