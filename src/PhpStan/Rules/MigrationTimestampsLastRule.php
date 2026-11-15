<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use AdamczykPiotr\UltimateFormatter\PhpStan\Rules\Support\MigrationSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<CallLike>
 */
class MigrationTimestampsLastRule implements Rule
{
    protected const TIMESTAMPS_METHODS = ['timestamps', 'timestampsTz', 'nullableTimestamps', 'nullableTimestampsTz'];

    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $call = MigrationSchema::migrationCall($node, $scope);
        if ($call === null || MigrationSchema::isSchemaCall($call, $scope) === false) {
            return [];
        }

        $errors = [];
        foreach ($call->getArgs() as $arg) {
            if ($arg->value instanceof Closure) {
                $errors = [...$errors, ...$this->columnsAfterTimestamps($arg->value)];
            }
        }

        return $errors;
    }

    /**
     * @param Closure $closure
     *
     * @return list<IdentifierRuleError>
     */
    protected function columnsAfterTimestamps(Closure $closure): array
    {
        $table = $closure->params[0]->var ?? null;
        if ($table instanceof Variable === false || is_string($table->name) === false) {
            return [];
        }

        $errors = [];
        $timestampsDefined = false;
        foreach ($closure->stmts as $statement) {
            $method = $this->blueprintMethod($statement, $table->name);
            if ($method === null || $this->definesColumns($method) === false) {
                continue;
            }

            if ($timestampsDefined === true) {
                $errors[] = RuleErrorBuilder::message("Column {$method}() is defined after timestamps(), created_at and updated_at stay the last columns.")
                    ->identifier('ultimateFormatter.migrationTimestampsLast')
                    ->line($statement->getStartLine())
                    ->build();
            }

            $timestampsDefined = $timestampsDefined || in_array($method, self::TIMESTAMPS_METHODS, true);
        }

        return $errors;
    }

    /**
     * @param Stmt $statement
     * @param string $table
     *
     * @return string|null
     */
    protected function blueprintMethod(Stmt $statement, string $table): ?string
    {
        if ($statement instanceof Expression === false) {
            return null;
        }

        $root = MigrationSchema::rootCall($statement->expr);
        if ($root === null || $root->var instanceof Variable === false || $root->var->name !== $table) {
            return null;
        }

        return MigrationSchema::methodName($root);
    }

    /**
     * Column methods return a column definition, or a collection of them like timestamps(); the morphs return nothing.
     *
     * @param string $method
     *
     * @return bool
     */
    protected function definesColumns(string $method): bool
    {
        if (Str::endsWith(Str::lower($method), 'morphs')) {
            return Str::startsWith($method, 'drop') === false;
        }

        $blueprint = $this->reflectionProvider->getClass(Blueprint::class);
        if ($blueprint->hasNativeMethod($method) === false) {
            return false;
        }

        $returnType = $blueprint->getNativeMethod($method)->getVariants()[0]->getReturnType();
        $columnType = new ObjectType(ColumnDefinition::class);

        return $columnType->isSuperTypeOf($returnType)->yes() || $columnType->isSuperTypeOf($returnType->getIterableValueType())->yes();
    }
}
