<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use AdamczykPiotr\UltimateFormatter\PhpStan\Rules\Support\MigrationSchema;
use Illuminate\Database\Schema\Blueprint;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Larastan reads every closure form, but without the Blueprint type PHPStan doesn't know what $table is, so the other
 * migration rules can't check the calls on it.
 *
 * @implements Rule<CallLike>
 */
class MigrationSchemaClosureRule implements Rule
{
    /**
     * Only these pass a Blueprint to their closure; withoutForeignKeyConstraints() and the like take one without.
     */
    protected const BLUEPRINT_CLOSURE_METHODS = ['create', 'table', 'whenTableHasColumn', 'whenTableDoesntHaveColumn', 'whenTableHasIndex', 'whenTableDoesntHaveIndex'];

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

        if (in_array(MigrationSchema::methodName($call), self::BLUEPRINT_CLOSURE_METHODS, true) === false) {
            return [];
        }

        $errors = [];
        foreach ($call->getArgs() as $arg) {
            $callback = $arg->value;
            if ($callback instanceof ArrowFunction || ($callback instanceof Closure && $this->hasSchemaSignature($callback, $scope) === false)) {
                $errors[] = RuleErrorBuilder::message('Schema closures are function (Blueprint $table): void, never fn, so PHPStan knows $table is a Blueprint and the migration rules can check it.')
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
