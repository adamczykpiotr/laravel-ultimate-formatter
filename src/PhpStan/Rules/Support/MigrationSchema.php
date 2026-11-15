<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules\Support;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;

class MigrationSchema
{
    /**
     * @param Scope $scope
     *
     * @return bool
     */
    public static function isInMigration(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        return $class !== null && $class->is(Migration::class);
    }

    /**
     * The node as a named-method or static call inside a migration, or null for anything else.
     *
     * @param Node $node
     * @param Scope $scope
     *
     * @return StaticCall|MethodCall|null
     */
    public static function migrationCall(Node $node, Scope $scope): StaticCall|MethodCall|null
    {
        if (($node instanceof StaticCall || $node instanceof MethodCall) === false || $node->isFirstClassCallable()) {
            return null;
        }

        return self::isInMigration($scope) ? $node : null;
    }

    /**
     * The argument passed for a parameter, whether by position or by name.
     *
     * @param StaticCall|MethodCall $call
     * @param int $position
     * @param string $name
     *
     * @return Arg|null
     */
    public static function argument(StaticCall|MethodCall $call, int $position, string $name): ?Arg
    {
        $args = collect($call->getArgs())->values();
        $named = $args->first(fn(Arg $arg): bool => $arg->name?->toString() === $name);
        if ($named !== null) {
            return $named;
        }

        // Positional arguments always come before named ones.
        $positional = $args->get($position);

        return $positional !== null && $positional->name === null ? $positional : null;
    }

    /**
     * Also matches calls through Schema::connection(...), which return the Builder.
     *
     * @param Node $node
     * @param Scope $scope
     *
     * @return bool
     */
    public static function isSchemaCall(Node $node, Scope $scope): bool
    {
        if ($node instanceof StaticCall) {
            return $node->class instanceof Name
                && (new ObjectType(Schema::class))->isSuperTypeOf($scope->resolveTypeByName($node->class))->yes();
        }

        if ($node instanceof MethodCall) {
            return (new ObjectType(Builder::class))->isSuperTypeOf($scope->getType($node->var))->yes();
        }

        return false;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     *
     * @return bool
     */
    public static function isBlueprintCall(Node $node, Scope $scope): bool
    {
        return $node instanceof MethodCall
            && (new ObjectType(Blueprint::class))->isSuperTypeOf($scope->getType($node->var))->yes();
    }

    /**
     * @param StaticCall|MethodCall $call
     *
     * @return string|null
     */
    public static function methodName(StaticCall|MethodCall $call): ?string
    {
        return $call->name instanceof Identifier ? $call->name->toString() : null;
    }

    /**
     * The first call of a chain such as $table->string('name')->nullable(), that is $table->string('name').
     *
     * @param Expr $expression
     *
     * @return MethodCall|null
     */
    public static function rootCall(Expr $expression): ?MethodCall
    {
        $root = null;
        while ($expression instanceof MethodCall) {
            $root = $expression;
            $expression = $expression->var;
        }

        return $root;
    }
}
