<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class BlankLineAfterBannerCommentFixer implements FixerInterface
{
    /**
     * @param Tokens<Token> $tokens
     *
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_COMMENT);
    }

    /**
     * @return bool
     */
    public function isRisky(): bool
    {
        return false;
    }

    /**
     * @param SplFileInfo $file
     * @param Tokens<Token> $tokens
     *
     * @return void
     */
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_COMMENT) === false || $this->isBanner($token->getContent()) === false) {
                continue;
            }

            $whitespace = $tokens[$index + 1] ?? null;
            $nextMeaningful = $tokens->getNextMeaningfulToken($index);
            if ($whitespace?->isWhitespace() !== true || $nextMeaningful === null || $tokens[$nextMeaningful]->equals('}')) {
                continue;
            }

            // Only a single newline lacks the blank line; extra ones are `no_extra_blank_lines`' job.
            $content = $whitespace->getContent();
            if (substr_count($content, "\n") !== 1) {
                continue;
            }

            $tokens[$index + 1] = new Token([T_WHITESPACE, "\n" . $content]);
        }
    }

    /**
     * @return FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'A `/* | Section | */` banner comment must be followed by a blank line.',
            [new CodeSample("<?php\nclass Foo\n{\n    /*\n    | Accessors\n    */\n    public function bar(): void\n    {\n    }\n}\n")],
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'UltimateFormatter/blank_line_after_banner_comment';
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return bool
     */
    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    /**
     * @param string $comment
     *
     * @return bool
     */
    protected function isBanner(string $comment): bool
    {
        $lines = collect(explode("\n", $comment));
        if (str_starts_with($comment, '/**') || $lines->count() < 3) {
            return false;
        }

        return $lines->slice(1, -1)->every(fn(string $line): bool => str_starts_with(ltrim($line), '|'));
    }
}
