<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ExpandNullableDocblockTypesRector extends AbstractRector
{
    protected const NULLABLE_TAG_TYPE_PATTERN = '/(@param|@return|@var|@throws)(\s+)\?([^\s$|&()]+)/';

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Property::class];
    }

    /**
     * @param Node $node
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return null;
        }

        $original = $docComment->getText();
        $rewritten = $this->expandNullableTypes($original);
        if ($rewritten === $original) {
            return null;
        }

        $node->setDocComment(new Doc($rewritten, $docComment->getStartLine(), $docComment->getStartFilePos()));

        return $node;
    }

    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Expand shorthand nullable `?T` to `T|null` in @param, @return, @var, and @throws docblock tags',
            [
                new CodeSample(
                    <<<'BEFORE'
/**
 * @param ?string $name
 * @return ?User
 */
public function find(?string $name): ?User
BEFORE,
                    <<<'AFTER'
/**
 * @param string|null $name
 * @return User|null
 */
public function find(?string $name): ?User
AFTER,
                ),
            ],
        );
    }

    /**
     * @param string $docText
     * @return string
     */
    protected function expandNullableTypes(string $docText): string
    {
        $expanded = preg_replace_callback(
            self::NULLABLE_TAG_TYPE_PATTERN,
            static fn(array $match): string => "{$match[1]}{$match[2]}{$match[3]}|null",
            $docText,
        );

        return $expanded ?? $docText;
    }
}
