<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpStan\Rules;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassMethodNode>
 */
class ResourceKeysCamelCaseRule implements Rule
{
    /**
     * JsonResource methods that merge their array arguments into the output.
     */
    protected const MERGING_METHODS = ['merge', 'mergeWhen', 'mergeUnless', 'when', 'unless'];

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @param InClassMethodNode $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->getOriginalNode();
        if ($method->name->toLowerString() !== 'toarray' || $node->getClassReflection()->is(JsonResource::class) === false) {
            return [];
        }

        // Arrays returned from closures inside toArray(), such as a mapped collection, are output too.
        $returned = collect((new NodeFinder())->find($method->stmts ?? [], fn(Node $found): bool => $found instanceof Return_ || $found instanceof ArrowFunction))
            ->map(fn(Node $found): ?Expr => $found instanceof Return_ ? $found->expr : ($found instanceof ArrowFunction ? $found->expr : null))
            ->filter()
            ->all();

        /** @var array<int, String_> $keys */
        $keys = [];
        foreach ($returned as $expression) {
            $this->collectKeys($expression, $keys);
        }

        $errors = [];
        foreach ($keys as $key) {
            $expected = $this->camelKey($key->value);
            if ($key->value !== $expected) {
                $errors[] = RuleErrorBuilder::message("Resource key '{$key->value}' must be camelCase, like '{$expected}'.")
                    ->identifier('ultimateFormatter.resourceKeysCamelCase')
                    ->line($key->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * The camelCase form of a key. One leading underscore marks a meta key such as '_actions' and is kept.
     *
     * @param string $key
     *
     * @return string
     */
    protected function camelKey(string $key): string
    {
        $prefix = Str::startsWith($key, '_') ? '_' : '';

        return $prefix . Str::camel(Str::after($key, $prefix));
    }

    /**
     * Collects the string keys of the output array, its nested arrays and the arrays merged in with $this->merge() and the like.
     *
     * @param Expr $expression
     * @param array<int, String_> $keys
     *
     * @return void
     */
    protected function collectKeys(Expr $expression, array &$keys): void
    {
        if ($expression instanceof Array_) {
            foreach ($expression->items as $item) {
                if ($item->key instanceof String_) {
                    // Keyed by object, since a nested array is reached again through its closure's return.
                    $keys[spl_object_id($item->key)] = $item->key;
                }

                $this->collectKeys($item->value, $keys);
            }

            return;
        }

        if ($expression instanceof MethodCall
            && $expression->var instanceof Variable
            && $expression->var->name === 'this'
            && $expression->name instanceof Identifier
            && in_array($expression->name->toString(), self::MERGING_METHODS, true)
        ) {
            foreach ($expression->getArgs() as $arg) {
                $this->collectKeys($arg->value, $keys);
            }
        }
    }
}
