<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
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
            if ($token->isGivenKind([T_LNUMBER, T_DNUMBER]) === false || $this->isCode($tokens, $index)) {
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
     * IDs and years are codes, not quantities: `1990` reads as a year, `1_990` doesn't.
     *
     * @param Tokens<Token> $tokens
     * @param int $index
     *
     * @return bool
     */
    protected function isCode(Tokens $tokens, int $index): bool
    {
        return collect($this->targetNames($tokens, $index))->contains(function(string $name): bool {
            $words = $this->words($name);

            return in_array($words->last(), ['id', 'ids'], true)
                || $words->contains(fn(string $word): bool => Str::contains($word, 'year'));
        });
    }

    /**
     * @param Tokens<Token> $tokens
     * @param int $index
     *
     * @return list<string>
     */
    protected function targetNames(Tokens $tokens, int $index): array
    {
        // Step out of array literals, so `precinctIds: [5757, 10001]` still finds `precinctIds`.
        $boundary = $this->expressionBoundary($tokens, $index);
        while ($boundary !== null && $tokens[$boundary]->equalsAny([',', [CT::T_ARRAY_BRACKET_OPEN]])) {
            $arrayStart = $this->arrayStart($tokens, $boundary);
            if ($arrayStart === null) {
                return [];
            }

            $boundary = $this->expressionBoundary($tokens, $arrayStart);
        }

        if ($boundary === null || $tokens[$boundary]->equalsAny(['=', [T_DOUBLE_ARROW], [CT::T_NAMED_ARGUMENT_COLON]]) === false) {
            return [];
        }

        $target = $tokens->getPrevMeaningfulToken($boundary);
        if ($target === null || $tokens[$target]->isGivenKind([T_VARIABLE, T_STRING, T_CONSTANT_ENCAPSED_STRING, CT::T_NAMED_ARGUMENT_NAME]) === false) {
            return [];
        }

        $names = [Str::trim($tokens[$target]->getContent(), '$\'"')];

        // Enum cases are named after the code (`FIRST_CENSUS = 1921`), so the enum's name counts too.
        $case = $tokens->getPrevMeaningfulToken($target);
        $enum = $tokens->getPrevTokenOfKind($target, [[T_ENUM]]);
        if ($case !== null && $tokens[$case]->isGivenKind(T_CASE) && $enum !== null) {
            $enumName = $tokens->getNextMeaningfulToken($enum);
            $names[] = $enumName === null ? '' : $tokens[$enumName]->getContent();
        }

        return $names;
    }

    /**
     * The token before the expression that holds `$index`, skipping arithmetic so `year: 2000 + $index` finds `:`.
     *
     * @param Tokens<Token> $tokens
     * @param int $index
     *
     * @return int|null
     */
    protected function expressionBoundary(Tokens $tokens, int $index): ?int
    {
        $boundary = $tokens->getPrevMeaningfulToken($index);
        while ($boundary !== null && $this->continuesExpression($tokens[$boundary])) {
            $boundary = $tokens->getPrevMeaningfulToken($boundary);
        }

        return $boundary;
    }

    /**
     * The opening `[` of the short array that `$index` sits in, or null outside of one.
     *
     * @param Tokens<Token> $tokens
     * @param int $index
     *
     * @return int|null
     */
    protected function arrayStart(Tokens $tokens, int $index): ?int
    {
        $depth = 0;
        for ($cursor = $index; $cursor >= 0; --$cursor) {
            $token = $tokens[$cursor];
            if ($token->equalsAny([')', ']', '}', [CT::T_ARRAY_BRACKET_CLOSE]])) {
                ++$depth;
            } elseif ($token->equalsAny(['(', '[', '{', [CT::T_ARRAY_BRACKET_OPEN]])) {
                if ($depth === 0) {
                    return $token->isGivenKind(CT::T_ARRAY_BRACKET_OPEN) ? $cursor : null;
                }

                --$depth;
            }
        }

        return null;
    }

    /**
     * @param Token $token
     *
     * @return bool
     */
    protected function continuesExpression(Token $token): bool
    {
        return $token->isGivenKind([T_LNUMBER, T_DNUMBER, T_VARIABLE])
            || $token->equalsAny(['+', '-', '*', '/', '%']);
    }

    /**
     * @param string $name
     *
     * @return Collection<int, non-falsy-string>
     */
    protected function words(string $name): Collection
    {
        // All-caps names (`FIRST_YEAR`) would otherwise split into single letters.
        $normalized = Str::upper($name) === $name ? Str::lower($name) : $name;

        return Str::of(Str::snake($normalized))
            ->explode('_')
            ->filter()
            ->values();
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
