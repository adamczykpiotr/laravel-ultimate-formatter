<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector\Rules;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ExpandNullableDocblockTypesRector extends AbstractRector
{
    protected const TAGS = ['@param', '@return', '@var', '@throws'];

    protected ?Lexer $lexer = null;
    protected ?TypeParser $typeParser = null;

    /**
     * @return list<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Property::class];
    }

    /**
     * @param Node $node
     *
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
     * Each `?T` right after a tag is parsed as a type, so generics, array shapes and callables are expanded whole.
     *
     * @param string $docText
     *
     * @return string
     */
    protected function expandNullableTypes(string $docText): string
    {
        $config = new ParserConfig([]);
        $this->lexer ??= new Lexer($config);
        $this->typeParser ??= new TypeParser($config, new ConstExprParser($config));

        $tokens = $this->lexer->tokenize($docText);
        $replacements = [];
        $offset = 0;
        foreach ($tokens as $index => $token) {
            if ($token[Lexer::TYPE_OFFSET] === Lexer::TOKEN_PHPDOC_TAG && in_array($token[Lexer::VALUE_OFFSET], self::TAGS, true)) {
                $replacements[] = $this->expandTagType($tokens, $index + 1, $offset + strlen($token[Lexer::VALUE_OFFSET]));
            }

            $offset += strlen($token[Lexer::VALUE_OFFSET]);
        }

        // From the last tag back, so a rewrite never shifts the offsets of the tags before it.
        return collect($replacements)
            ->filter()
            ->reverse()
            ->reduce(fn(string $text, array $replacement): string => substr_replace($text, $replacement[2], $replacement[0], $replacement[1]), $docText);
    }

    /**
     * @param list<array{string, int, int}> $tokens
     * @param int $index the token right after the tag
     * @param int $offset the text offset of that token
     *
     * @return array{int, int, string}|null the offset and length of `?T`, and the `T|null` to put there
     */
    protected function expandTagType(array $tokens, int $index, int $offset): ?array
    {
        while (($tokens[$index][Lexer::TYPE_OFFSET] ?? null) === Lexer::TOKEN_HORIZONTAL_WS) {
            $offset += strlen($tokens[$index][Lexer::VALUE_OFFSET]);
            ++$index;
        }

        if (($tokens[$index][Lexer::TYPE_OFFSET] ?? null) !== Lexer::TOKEN_NULLABLE) {
            return null;
        }

        $iterator = new TokenIterator($tokens, $index);

        try {
            $type = $this->typeParser?->parse($iterator);
        } catch (ParserException) {
            return null;
        }

        if ($type instanceof NullableTypeNode === false) {
            return null;
        }

        $innerText = collect($tokens)
            ->slice($index + 1, $iterator->currentTokenIndex() - $index - 1)
            ->map(fn(array $token): string => $token[Lexer::VALUE_OFFSET])
            ->implode('');
        $innerText = rtrim($innerText);
        if ($innerText === '') {
            return null;
        }

        $expanded = $type->type instanceof CallableTypeNode ? "({$innerText})|null" : "{$innerText}|null";

        return [$offset, strlen($innerText) + 1, $expanded];
    }
}
