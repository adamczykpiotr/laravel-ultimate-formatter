<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use SplFileInfo;

/**
 * Merges the trait `use` statements of a class into the first one, the opposite of PHP CS Fixer's
 * `single_trait_insert_per_statement`. `ordered_traits` then sorts the names. A class whose trait
 * statements carry a `{ ... }` conflict resolution block or a comment is left alone.
 */
class SingleTraitUseStatementFixer implements FixerInterface
{
    /**
     * @param Tokens<Token> $tokens
     *
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(CT::T_USE_TRAIT);
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
        /** @var array<int, list<int>> $startsByClass */
        $startsByClass = [];
        foreach ((new TokensAnalyzer($tokens))->getClassyElements() as $index => $element) {
            if ($element['type'] === 'trait_import') {
                $startsByClass[$element['classIndex']][] = $index;
            }
        }

        // From the last class back, so merging one class never shifts the indexes of another.
        krsort($startsByClass);
        foreach ($startsByClass as $starts) {
            if (count($starts) < 2) {
                continue;
            }

            sort($starts);
            $ranges = [];
            foreach ($starts as $start) {
                $range = $this->simpleStatementRange($tokens, $start);
                if ($range === null) {
                    continue 2;
                }

                $ranges[] = $range;
            }

            $this->merge($tokens, $ranges);
        }
    }

    /**
     * @return FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'All traits of a class are imported in one `use` statement.',
            [new CodeSample("<?php\nclass Foo\n{\n    use HasFactory;\n    use SoftDeletes;\n}\n")],
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'UltimateFormatter/single_trait_use_statement';
    }

    /**
     * Must run before OrderedTraitsFixer, OrderedClassElementsFixer and ClassAttributesSeparationFixer.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 70;
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
     * The index of the closing `;` of a `use A, B;` statement, or null for anything else.
     *
     * @param Tokens<Token> $tokens
     * @param int $start
     *
     * @return array{int, int}|null
     */
    protected function simpleStatementRange(Tokens $tokens, int $start): ?array
    {
        $end = $tokens->getNextTokenOfKind($start, [';', '{']);
        if ($end === null || $tokens[$end]->equals('{')) {
            return null;
        }

        for ($index = $start + 1; $index < $end; ++$index) {
            if ($tokens[$index]->isComment()) {
                return null;
            }
        }

        return [$start, $end];
    }

    /**
     * @param Tokens<Token> $tokens
     * @param list<array{int, int}> $ranges
     *
     * @return void
     */
    protected function merge(Tokens $tokens, array $ranges): void
    {
        $names = [];
        foreach ($ranges as [$start, $end]) {
            $names = [...$names, ...$this->names($tokens, $start, $end)];
        }

        // Later statements first, so the first statement's indexes stay put until it is rewritten.
        foreach (array_reverse(array_slice($ranges, 1)) as [$start, $end]) {
            $tokens->clearRange($start, $end);
            $tokens->removeLeadingWhitespace($start);
        }

        [$firstStart, $firstEnd] = $ranges[0];
        $merged = [new Token([CT::T_USE_TRAIT, 'use']), new Token([T_WHITESPACE, ' '])];
        foreach ($names as $position => $name) {
            if ($position > 0) {
                $merged[] = new Token(',');
                $merged[] = new Token([T_WHITESPACE, ' ']);
            }

            $merged = [...$merged, ...$name];
        }

        $merged[] = new Token(';');

        $tokens->overrideRange($firstStart, $firstEnd, $merged);
    }

    /**
     * The tokens of each trait name in one statement, without the whitespace around them.
     *
     * @param Tokens<Token> $tokens
     * @param int $start
     * @param int $end
     *
     * @return list<list<Token>>
     */
    protected function names(Tokens $tokens, int $start, int $end): array
    {
        $names = [];
        $current = [];
        for ($index = $start + 1; $index < $end; ++$index) {
            $token = $tokens[$index];
            if ($token->equals(',')) {
                $names[] = $current;
                $current = [];
            } elseif ($token->isWhitespace() === false) {
                $current[] = clone $token;
            }
        }

        $names[] = $current;

        return $names;
    }
}
