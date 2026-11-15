<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class GroupTraitUsesRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassLike::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof ClassLike === false) {
            return null;
        }

        $traitUses = collect($node->stmts)->filter(fn(Node $stmt): bool => $stmt instanceof TraitUse);
        if ($traitUses->isEmpty()) {
            return null;
        }

        /** @var TraitUse $firstTraitUse */
        $firstTraitUse = $traitUses->first();
        $traits = $traitUses
            ->flatMap(fn(TraitUse $traitUse): array => $traitUse->traits)
            ->unique(fn(Name $trait): string => strtolower($this->getName($trait)))
            ->sortBy(fn(Name $trait): string => strtolower($trait->getLast()), SORT_STRING)
            ->values();

        $isAlreadyGrouped = $traitUses->count() === 1
            && $node->stmts[0] === $firstTraitUse
            && $traits->all() === $firstTraitUse->traits;
        if ($isAlreadyGrouped) {
            return null;
        }

        $adaptations = $traitUses->flatMap(fn(TraitUse $traitUse): array => $traitUse->adaptations)->all();
        $groupedTraitUse = new TraitUse($traits->all(), $adaptations, $firstTraitUse->getAttributes());
        $otherStmts = collect($node->stmts)->reject(fn(Node $stmt): bool => $stmt instanceof TraitUse);

        $node->stmts = [$groupedTraitUse, ...$otherStmts->values()->all()];

        return $node;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Group every trait of a class into one alphabetically ordered `use` statement at the top of the class body',
            [
                new CodeSample(
                    <<<'BEFORE'
class Tag extends Model
{
    public const ATTRIBUTE_NAME = 'name';

    use SoftDeletes;
    use HasFactory;
}
BEFORE,
                    <<<'AFTER'
class Tag extends Model
{
    use HasFactory, SoftDeletes;

    public const ATTRIBUTE_NAME = 'name';
}
AFTER,
                ),
            ],
        );
    }
}
