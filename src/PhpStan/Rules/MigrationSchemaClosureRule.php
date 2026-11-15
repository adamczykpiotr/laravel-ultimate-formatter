<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use AdamczykPiotr\UltimateFormatter\PhpStan\Rules\Support\MigrationSchema;
use Illuminate\Database\Schema\Blueprint;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Larastan skips arrow functions when it reads the schema from the migrations.
 *
 * @implements Rule<CallLike>
 */
class MigrationSchemaClosureRule implements Rule
{
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

        if (MigrationSchema::isInMigration($scope) === false || MigrationSchema::isSchemaCall($node, $scope) === false) {
            return [];
        }

        $errors = [];
        foreach ($node->getArgs() as $arg) {
            $callback = $arg->value;
            if ($callback instanceof ArrowFunction || ($callback instanceof Closure && $this->hasSchemaSignature($callback, $scope) === false)) {
                $errors[] = RuleErrorBuilder::message('Schema closures are function (Blueprint $table): void, never fn, so Larastan can read the schema.')
                    ->identifier('ultimateFormatter.migrationSchemaClosure')
                    ->line($callback->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @param Closure $closure
     * @param Scope $scope
     *
     * @return bool
     */
    protected function hasSchemaSignature(Closure $closure, Scope $scope): bool
    {
        $type = $closure->params[0]->type ?? null;

        return $type instanceof Name
            && $scope->resolveName($type) === Blueprint::class
            && $closure->returnType instanceof Identifier
            && $closure->returnType->toLowerString() === 'void';
    }
}
