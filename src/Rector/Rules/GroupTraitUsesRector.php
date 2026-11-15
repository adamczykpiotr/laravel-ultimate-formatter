<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\TraitUse;
use Rector\NodeTypeResolver\Node\AttributeKey;
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
        $attributes = [...$firstTraitUse->getAttributes(), 'comments' => $this->mergedComments($traitUses)];
        unset($attributes[AttributeKey::PHP_DOC_INFO]);
        $groupedTraitUse = new TraitUse($traits->all(), $adaptations, $attributes);
        $otherStmts = collect($node->stmts)->reject(fn(Node $stmt): bool => $stmt instanceof TraitUse);

        $node->stmts = [$groupedTraitUse, ...$otherStmts->values()->all()];

        return $node;
    }

    /**
     * Keeps the comments of every trait use, joining their docblocks into one, since PHPStan reads only the last docblock
     * before a statement and would miss the other @use tags.
     *
     * @param Collection<int, TraitUse> $traitUses
     *
     * @return array<int, Comment>
     */
    protected function mergedComments(Collection $traitUses): array
    {
        $comments = $traitUses->flatMap(fn(TraitUse $traitUse): array => $traitUse->getComments());
        $docComments = $comments->filter(fn(Comment $comment): bool => $comment instanceof Doc);
        if ($docComments->count() <= 1) {
            return $comments->values()->all();
        }

        $tags = $docComments->flatMap(fn(Comment $docComment): array => $this->docblockLines($docComment));
        $mergedDocComment = new Doc("/**\n" . $tags->map(fn(string $tag): string => " * {$tag}")->implode("\n") . "\n */");
        $otherComments = $comments->reject(fn(Comment $comment): bool => $comment instanceof Doc);

        return [...$otherComments->values()->all(), $mergedDocComment];
    }

    /**
     * @param Comment $docComment
     *
     * @return array<int, string>
     */
    protected function docblockLines(Comment $docComment): array
    {
        return Str::of($docComment->getText())
            ->after('/**')
            ->beforeLast('*/')
            ->explode("\n")
            ->map(fn(string $line): string => trim(ltrim(trim($line), '*')))
            ->filter(fn(string $line): bool => $line !== '')
            ->values()
            ->all();
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
