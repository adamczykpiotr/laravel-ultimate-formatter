<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class FactoryAttributeKeysToConstantsRector extends AbstractRector
{
    protected const FACTORY_CLASS = 'Illuminate\Database\Eloquent\Factories\Factory';
    protected const ATTRIBUTE_METHODS = [
        'create', 'createOne', 'createOneQuietly', 'createQuietly', 'make', 'makeOne', 'raw', 'sequence', 'state',
    ];

    /**
     * @var array<string, array<string, list<string>>>
     */
    protected array $attributeConstantsByModel = [];

    /**
     * @param ReflectionProvider $reflectionProvider
     * @param BetterNodeFinder $betterNodeFinder
     */
    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly BetterNodeFinder $betterNodeFinder,
    ) {}

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, MethodCall::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        $modelClass = match (true) {
            $node instanceof ClassMethod => $this->resolveDefinitionModel($node),
            $node instanceof MethodCall => $this->resolveCallModel($node),
            default => null,
        };
        if ($modelClass === null) {
            return null;
        }

        $arrays = $node instanceof ClassMethod
            ? $this->findReturnedArrays($node)
            : collect($node->getArgs())->flatMap(fn(Arg $arg): array => $this->arraysOf($arg->value))->all();

        $hasChanged = false;
        foreach ($arrays as $array) {
            $hasChanged = $this->replaceKeys($array, $modelClass) || $hasChanged;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Key model factory attribute arrays by the model\'s `ATTRIBUTE_*` constants',
            [
                new CodeSample(
                    <<<'BEFORE'
public function definition(): array
{
    return ['name' => fake()->word()];
}

Tag::factory()->create(['name' => 'Urgent']);
BEFORE,
                    <<<'AFTER'
public function definition(): array
{
    return [Tag::ATTRIBUTE_NAME => fake()->word()];
}

Tag::factory()->create([Tag::ATTRIBUTE_NAME => 'Urgent']);
AFTER,
                ),
            ],
        );
    }

    /**
     * @param ClassMethod $classMethod
     *
     * @return string|null
     */
    protected function resolveDefinitionModel(ClassMethod $classMethod): ?string
    {
        if ($this->isName($classMethod, 'definition') === false) {
            return null;
        }

        $classReflection = ScopeFetcher::fetch($classMethod)->getClassReflection();
        if ($classReflection === null || $classReflection->is(self::FACTORY_CLASS) === false) {
            return null;
        }

        $templateType = $classReflection->getAncestorWithClassName(self::FACTORY_CLASS)
            ?->getActiveTemplateTypeMap()
            ->getType('TModel');

        return $this->modelFromType($templateType) ?? $this->modelFromFactoryClass($classReflection);
    }

    /**
     * @param MethodCall $methodCall
     *
     * @return string|null
     */
    protected function resolveCallModel(MethodCall $methodCall): ?string
    {
        if ($this->isNames($methodCall->name, self::ATTRIBUTE_METHODS) === false || $methodCall->isFirstClassCallable()) {
            return null;
        }

        $factoryType = $this->getType($methodCall->var);
        $factoryClassReflections = $factoryType->getObjectClassReflections();
        if (count($factoryClassReflections) !== 1 || $factoryClassReflections[0]->is(self::FACTORY_CLASS) === false) {
            return null;
        }

        $templateType = $factoryType->getTemplateType(self::FACTORY_CLASS, 'TModel');

        return $this->modelFromType($templateType) ?? $this->modelFromFactoryClass($factoryClassReflections[0]);
    }

    /**
     * @param Type|null $type
     *
     * @return string|null
     */
    protected function modelFromType(?Type $type): ?string
    {
        $classNames = $type?->getObjectClassNames() ?? [];
        if (count($classNames) !== 1 || $classNames[0] === 'Illuminate\Database\Eloquent\Model') {
            return null;
        }

        return $classNames[0];
    }

    /**
     * @param ClassReflection $factoryReflection
     *
     * @return string|null
     */
    protected function modelFromFactoryClass(ClassReflection $factoryReflection): ?string
    {
        $nativeReflection = $factoryReflection->getNativeReflection();
        $modelProperty = $nativeReflection->hasProperty('model') ? $nativeReflection->getProperty('model') : null;
        $declaredModel = $modelProperty?->getDefaultValue();
        if (is_string($declaredModel) && $this->reflectionProvider->hasClass($declaredModel)) {
            return $declaredModel;
        }

        $factoryName = $factoryReflection->getName();
        if (str_starts_with($factoryName, 'Database\\Factories\\') === false) {
            return null;
        }

        $relativeName = Str::of($factoryName)->after('Database\\Factories\\')->beforeLast('Factory')->toString();
        $conventionalModel = "App\\Models\\{$relativeName}";

        return $this->reflectionProvider->hasClass($conventionalModel) ? $conventionalModel : null;
    }

    /**
     * @param FunctionLike $functionLike
     *
     * @return array<int, Array_>
     */
    protected function findReturnedArrays(FunctionLike $functionLike): array
    {
        return collect($this->betterNodeFinder->findReturnsScoped($functionLike))
            ->map(fn(Return_ $return): ?Expr => $return->expr)
            ->filter(fn(?Expr $expr): bool => $expr instanceof Array_)
            ->values()
            ->all();
    }

    /**
     * @param Expr $expr
     *
     * @return array<int, Array_>
     */
    protected function arraysOf(Expr $expr): array
    {
        if ($expr instanceof Array_) {
            return [$expr];
        }

        if ($expr instanceof ArrowFunction) {
            return $this->arraysOf($expr->expr);
        }

        if ($expr instanceof Closure) {
            return $this->findReturnedArrays($expr);
        }

        return [];
    }

    /**
     * @param Array_ $array
     * @param string $modelClass
     *
     * @return bool
     */
    protected function replaceKeys(Array_ $array, string $modelClass): bool
    {
        $constantsByValue = $this->attributeConstants($modelClass);
        $hasChanged = false;

        foreach ($array->items as $item) {
            if ($item->key instanceof String_ === false) {
                continue;
            }

            $constantNames = $constantsByValue[$item->key->value] ?? [];
            if (count($constantNames) !== 1) {
                continue;
            }

            $item->key = new ClassConstFetch(new FullyQualified($modelClass), $constantNames[0]);
            $hasChanged = true;
        }

        return $hasChanged;
    }

    /**
     * @param string $modelClass
     *
     * @return array<string, list<string>>
     */
    protected function attributeConstants(string $modelClass): array
    {
        if (isset($this->attributeConstantsByModel[$modelClass])) {
            return $this->attributeConstantsByModel[$modelClass];
        }

        $constantsByValue = [];
        $nativeReflection = $this->reflectionProvider->getClass($modelClass)->getNativeReflection();
        foreach ($nativeReflection->getReflectionConstants() as $constant) {
            $value = $constant->getValue();
            if (str_starts_with($constant->getName(), 'ATTRIBUTE_') && is_string($value)) {
                $constantsByValue[$value][] = $constant->getName();
            }
        }

        return $this->attributeConstantsByModel[$modelClass] = $constantsByValue;
    }
}
