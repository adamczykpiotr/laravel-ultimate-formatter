<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use AdamczykPiotr\UltimateFormatter\PhpStan\Rules\Support\MigrationSchema;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Larastan only follows schema calls whose table and column names it can read, so a dropped column named through a
 * variable stays typed as present on the model.
 *
 * @implements Rule<CallLike>
 */
class MigrationLiteralSchemaNamesRule implements Rule
{
    /**
     * Column definition methods whose arguments are all table, column or index names.
     */
    protected const COLUMN_DEFINITION_NAME_METHODS = ['after', 'constrained', 'on', 'references'];

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
        if (($node instanceof StaticCall || $node instanceof MethodCall) === false || $node->isFirstClassCallable()) {
            return [];
        }

        $method = MigrationSchema::methodName($node);
        if ($method === null || MigrationSchema::isInMigration($scope) === false) {
            return [];
        }

        // The schema closures take no names; MigrationSchemaClosureRule checks them.
        $names = collect($this->nameArguments($node, $method, $scope))
            ->reject(fn(Arg $arg): bool => $arg->value instanceof Closure || $arg->value instanceof ArrowFunction);
        if ($names->every(fn(Arg $arg): bool => $arg->unpack === false && $this->isLiteral($arg->value))) {
            return [];
        }

        return [
            RuleErrorBuilder::message("{$this->callLabel($node, $method)} must name tables and columns with string literals, not variables, properties, constants or loops, so Larastan can read the schema.")
                ->identifier('ultimateFormatter.migrationLiteralSchemaNames')
                ->build(),
        ];
    }

    /**
     * The arguments of the call that hold table, column or index names.
     *
     * @param StaticCall|MethodCall $call
     * @param string $method
     * @param Scope $scope
     *
     * @return list<Arg>
     */
    protected function nameArguments(StaticCall|MethodCall $call, string $method, Scope $scope): array
    {
        $args = array_values($call->getArgs());

        if (MigrationSchema::isSchemaCall($call, $scope)) {
            // The connection name isn't part of the schema.
            return $method === 'connection' ? [] : $args;
        }

        if (MigrationSchema::isBlueprintCall($call, $scope)) {
            // The other arguments of a column definition are lengths, allowed values and other settings.
            return Str::startsWith($method, ['drop', 'rename']) ? $args : array_slice($args, 0, 1);
        }

        if ($call instanceof MethodCall && in_array($method, self::COLUMN_DEFINITION_NAME_METHODS, true)) {
            $calledOn = $scope->getType($call->var);
            if ((new ObjectType(ColumnDefinition::class))->isSuperTypeOf($calledOn)->yes() || (new ObjectType(ForeignKeyDefinition::class))->isSuperTypeOf($calledOn)->yes()) {
                return $args;
            }
        }

        return [];
    }

    /**
     * How the call reads in the message: Schema::create(), $table->string() or ->constrained().
     *
     * @param StaticCall|MethodCall $call
     * @param string $method
     *
     * @return string
     */
    protected function callLabel(StaticCall|MethodCall $call, string $method): string
    {
        if ($call instanceof StaticCall) {
            return "Schema::{$method}()";
        }

        if ($call->var instanceof Variable && is_string($call->var->name)) {
            return "\${$call->var->name}->{$method}()";
        }

        return "->{$method}()";
    }

    /**
     * @param Expr $expression
     *
     * @return bool
     */
    protected function isLiteral(Expr $expression): bool
    {
        if ($expression instanceof String_ || $expression instanceof Int_ || $expression instanceof Float_) {
            return true;
        }

        if ($expression instanceof ConstFetch) {
            return in_array($expression->name->toLowerString(), ['true', 'false', 'null'], true);
        }

        if ($expression instanceof Array_) {
            return collect($expression->items)->every(fn(ArrayItem $item): bool => $item->unpack === false
                && ($item->key === null || $this->isLiteral($item->key))
                && $this->isLiteral($item->value));
        }

        return false;
    }
}
