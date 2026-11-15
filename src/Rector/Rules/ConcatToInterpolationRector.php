<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ConcatToInterpolationRector extends AbstractRector
{
    protected const SKIP_ATTRIBUTE = 'ultimate_formatter_skip_concat';

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Concat::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Concat === false || $node->getAttribute(self::SKIP_ATTRIBUTE) === true) {
            return null;
        }

        $operands = collect($this->flattenOperands($node));
        $isConvertible = $node->getStartLine() === $node->getEndLine()
            && $operands->every(fn(Expr $operand): bool => $this->isInterpolatable($operand));

        if ($isConvertible === false) {
            $this->markInnerConcatsSkipped($node);

            return null;
        }

        $parts = $this->mergeAdjacentStringParts($operands->flatMap(fn(Expr $operand): array => $this->toParts($operand)));
        $hasExpressionPart = $parts->contains(fn(Expr|InterpolatedStringPart $part): bool => $part instanceof InterpolatedStringPart === false);
        if ($hasExpressionPart === false) {
            /** @var InterpolatedStringPart|null $onlyPart */
            $onlyPart = $parts->first();

            return new String_($onlyPart->value ?? '');
        }

        return new InterpolatedString($parts->all(), ['kind' => String_::KIND_DOUBLE_QUOTED]);
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace string concatenation with interpolation',
            [
                new CodeSample(
                    <<<'BEFORE'
$message = 'Imported ' . $count . ' readings for ' . $meter->name;
BEFORE,
                    <<<'AFTER'
$message = "Imported {$count} readings for {$meter->name}";
AFTER,
                ),
            ],
        );
    }

    /**
     * @param Expr $expr
     *
     * @return list<Expr>
     */
    protected function flattenOperands(Expr $expr): array
    {
        if ($expr instanceof Concat === false) {
            return [$expr];
        }

        return [...$this->flattenOperands($expr->left), ...$this->flattenOperands($expr->right)];
    }

    /**
     * @param Concat $concat
     *
     * @return void
     */
    protected function markInnerConcatsSkipped(Concat $concat): void
    {
        $sides = [$concat->left, $concat->right];
        foreach ($sides as $side) {
            if ($side instanceof Concat) {
                $side->setAttribute(self::SKIP_ATTRIBUTE, true);
                $this->markInnerConcatsSkipped($side);
            }
        }
    }

    /**
     * @param Expr $operand
     *
     * @return bool
     */
    protected function isInterpolatable(Expr $operand): bool
    {
        if ($operand instanceof String_) {
            return in_array($operand->getAttribute('kind'), [String_::KIND_SINGLE_QUOTED, String_::KIND_DOUBLE_QUOTED], true);
        }

        if ($operand instanceof InterpolatedString) {
            return $operand->getAttribute('kind') === String_::KIND_DOUBLE_QUOTED;
        }

        return $this->isVariableRead($operand);
    }

    /**
     * @param Expr $expr
     *
     * @return bool
     */
    protected function isVariableRead(Expr $expr): bool
    {
        if ($expr instanceof Variable) {
            return is_string($expr->name);
        }

        if ($expr instanceof PropertyFetch || $expr instanceof NullsafePropertyFetch) {
            return $expr->name instanceof Identifier && $this->isVariableRead($expr->var);
        }

        if ($expr instanceof ArrayDimFetch) {
            $isSimpleKey = $expr->dim instanceof String_ || $expr->dim instanceof Int_ || $expr->dim instanceof Variable;

            return $isSimpleKey && $this->isVariableRead($expr->var);
        }

        return false;
    }

    /**
     * @param Expr $operand
     *
     * @return array<array-key, Expr|InterpolatedStringPart>
     */
    protected function toParts(Expr $operand): array
    {
        if ($operand instanceof String_) {
            return [new InterpolatedStringPart($operand->value)];
        }

        if ($operand instanceof InterpolatedString) {
            return $operand->parts;
        }

        return [$operand];
    }

    /**
     * @param Collection<int, Expr|InterpolatedStringPart> $parts
     *
     * @return Collection<int, Expr|InterpolatedStringPart>
     */
    protected function mergeAdjacentStringParts(Collection $parts): Collection
    {
        return $parts
            ->reduce(function (Collection $merged, Expr|InterpolatedStringPart $part): Collection {
                $previous = $merged->last();
                if ($part instanceof InterpolatedStringPart && $previous instanceof InterpolatedStringPart) {
                    $merged->pop();

                    return $merged->push(new InterpolatedStringPart("{$previous->value}{$part->value}"));
                }

                return $merged->push($part);
            }, collect())
            ->reject(fn(Expr|InterpolatedStringPart $part): bool => $part instanceof InterpolatedStringPart && $part->value === '')
            ->values();
    }
}
