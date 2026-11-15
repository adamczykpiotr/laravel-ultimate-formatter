<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers;

use Illuminate\Support\Str;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class NumericLiteralSeparatorFixer implements FixerInterface
{
    protected const MIN_DIGITS = 4;
    protected const GROUP_SIZE = 3;

    /**
     * @param Tokens<Token> $tokens
     *
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_LNUMBER, T_DNUMBER]);
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
            if ($token->isGivenKind([T_LNUMBER, T_DNUMBER]) === false) {
                continue;
            }

            $literal = $token->getContent();
            $separated = $this->separate($literal);
            if ($separated !== $literal) {
                $tokens[$index] = new Token([$token->getId(), $separated]);
            }
        }
    }

    /**
     * @return FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Decimal literals group their thousands with `_` separators.',
            [new CodeSample("<?php\n\$timeout = 30000;\n\$limit = 1000_000;\n")],
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'UltimateFormatter/numeric_literal_separator';
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
     * @param string $literal
     *
     * @return string
     */
    protected function separate(string $literal): string
    {
        // Hex, octal, binary and exponent literals keep the form they were written in.
        if (Str::isMatch('/^[1-9][0-9_]*(\\.[0-9_]*)?$/', $literal) === false) {
            return $literal;
        }

        $integer = Str::before($literal, '.');
        $fraction = Str::substr($literal, strlen($integer));
        $digits = Str::remove('_', $integer);
        if (strlen($digits) < self::MIN_DIGITS) {
            return "{$digits}{$fraction}";
        }

        // Split from the right, so the short group lands first: `12_345`, not `123_45`.
        $reversedGroups = Str::of($digits)->reverse()->split(self::GROUP_SIZE)->implode('_');
        $grouped = Str::reverse($reversedGroups);

        return "{$grouped}{$fraction}";
    }
}
