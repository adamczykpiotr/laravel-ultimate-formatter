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
     * The innermost opening bracket around each `,` and closing bracket, for the file being fixed.
     *
     * @var array<int, int>
     */
    protected array $openers = [];

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
        $this->openers = $this->openers($tokens);

        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind([T_LNUMBER, T_DNUMBER]) === false) {
                continue;
            }

            // Looking for the name is the slow part, so a literal that stays the same skips it.
            $literal = $token->getContent();
            $separated = $this->separate($literal);
            if ($separated !== $literal && $this->isCode($tokens, $index) === false) {
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
        $names = [];

        // Step out of array literals, so `precinctIds: [5757, 10001]` still finds `precinctIds`.
        $boundary = $this->expressionBoundary($tokens, $index, $names);
        while ($boundary !== null && $tokens[$boundary]->equalsAny([',', [CT::T_ARRAY_BRACKET_OPEN]])) {
            $arrayStart = $this->arrayStart($tokens, $boundary);
            if ($arrayStart === null) {
                return $names;
            }

            $boundary = $this->expressionBoundary($tokens, $arrayStart, $names);
        }

        if ($boundary === null || $tokens[$boundary]->equalsAny(['=', [T_DOUBLE_ARROW], [CT::T_NAMED_ARGUMENT_COLON]]) === false) {
            return $names;
        }

        $target = $this->nameToken($tokens, $tokens->getPrevMeaningfulToken($boundary));
        if ($target === null) {
            return $names;
        }

        $names[] = $this->name($tokens[$target]);

        // Enum cases are named after the code (`FIRST_CENSUS = 1921`), so the enum's name counts too.
        $case = $tokens->getPrevMeaningfulToken($target);
        if ($case !== null && $tokens[$case]->isGivenKind(T_CASE)) {
            $enum = $tokens->getPrevTokenOfKind($target, [[T_ENUM]]);
            $enumName = $enum === null ? null : $tokens->getNextMeaningfulToken($enum);
            $names[] = $enumName === null ? '' : $tokens[$enumName]->getContent();
        }

        return $names;
    }

    /**
     * The token that names the value ending at `$index`: `$userId`, the key of `$row['parent_id']`, or `$userIds` in `$userIds[]`.
     *
     * @param Tokens<Token> $tokens
     * @param int|null $index
     *
     * @return int|null
     */
    protected function nameToken(Tokens $tokens, ?int $index): ?int
    {
        if ($index !== null && $tokens[$index]->equals(']')) {
            $index = $tokens->getPrevMeaningfulToken($index);
        }

        if ($index !== null && $tokens[$index]->equals('[')) {
            $index = $tokens->getPrevMeaningfulToken($index);
        }

        if ($index === null || $tokens[$index]->isGivenKind([T_VARIABLE, T_STRING, T_CONSTANT_ENCAPSED_STRING, CT::T_NAMED_ARGUMENT_NAME]) === false) {
            return null;
        }

        return $index;
    }

    /**
     * @param Token $token
     *
     * @return string
     */
    protected function name(Token $token): string
    {
        return Str::trim($token->getContent(), '$\'"');
    }

    /**
     * The token before the expression that holds `$index`, skipping arithmetic so `year: 2000 + $index` finds `:`.
     * A `??` names its fallback after the value it stands in for, so `$row['userId'] ?? 10001` adds `userId` to `$names`.
     *
     * @param Tokens<Token> $tokens
     * @param int $index
     * @param list<string> $names
     *
     * @return int|null
     */
    protected function expressionBoundary(Tokens $tokens, int $index, array &$names): ?int
    {
        $boundary = $tokens->getPrevMeaningfulToken($index);
        while ($boundary !== null) {
            $token = $tokens[$boundary];
            if ($token->isGivenKind(T_COALESCE)) {
                $boundary = $tokens->getPrevMeaningfulToken($boundary);
                $operand = $this->nameToken($tokens, $boundary);
                if ($operand !== null) {
                    $names[] = $this->name($tokens[$operand]);
                }

                continue;
            }

            // Step over an index such as `['userId']`, to the variable it reads from.
            if ($token->equals(']') && isset($this->openers[$boundary])) {
                $boundary = $tokens->getPrevMeaningfulToken($this->openers[$boundary]);

                continue;
            }

            if ($this->continuesExpression($token) === false) {
                return $boundary;
            }

            $boundary = $tokens->getPrevMeaningfulToken($boundary);
        }

        return null;
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
        $opener = $tokens[$index]->isGivenKind(CT::T_ARRAY_BRACKET_OPEN) ? $index : $this->openers[$index] ?? null;

        return $opener !== null && $tokens[$opener]->isGivenKind(CT::T_ARRAY_BRACKET_OPEN) ? $opener : null;
    }

    /**
     * Built in one pass, so a long array doesn't walk back to its opening `[` once per element.
     *
     * @param Tokens<Token> $tokens
     *
     * @return array<int, int>
     */
    protected function openers(Tokens $tokens): array
    {
        $openers = [];
        /** @var Collection<int, int> $stack */
        $stack = collect();
        foreach ($tokens as $index => $token) {
            if ($token->equalsAny(['(', '[', '{', [CT::T_ARRAY_BRACKET_OPEN]])) {
                $stack->push($index);
            } elseif ($token->equalsAny([',', ')', ']', '}', [CT::T_ARRAY_BRACKET_CLOSE]])) {
                $opener = $token->equals(',') ? $stack->last() : $stack->pop();
                if ($opener !== null) {
                    $openers[$index] = $opener;
                }
            }
        }

        return $openers;
    }

    /**
     * @param Token $token
     *
     * @return bool
     */
    protected function continuesExpression(Token $token): bool
    {
        // The ternary's `?` and `:` too, so `$userId = $flag ? 10001 : 0` finds `$userId`.
        return $token->isGivenKind([T_LNUMBER, T_DNUMBER, T_VARIABLE])
            || $token->equalsAny(['+', '-', '*', '/', '%', '?', ':']);
    }

    /**
     * @param string $name
     *
     * @return Collection<int, non-falsy-string>
     */
    protected function words(string $name): Collection
    {
        // Capital runs (`FIRST_YEAR`, `EdielID`) would otherwise split into single letters.
        return Str::of($name)
            ->replaceMatches('/[A-Z]+(?=[A-Z][a-z]|[^A-Za-z]|$)/', $this->capitalize(...))
            ->snake()
            ->explode('_')
            ->filter()
            ->values();
    }

    /**
     * @param array<int, string> $match
     *
     * @return string
     */
    protected function capitalize(array $match): string
    {
        return Str::ucfirst(Str::lower($match[0]));
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
