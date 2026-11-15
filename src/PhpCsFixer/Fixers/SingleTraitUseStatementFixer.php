<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers;

use Illuminate\Support\Str;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use SplFileInfo;

class SingleTraitUseStatementFixer extends BaseFixer
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
                $comments = $this->leadingComments($tokens, $start);

                // Only a lone docblock, such as `@use HasFactory<UserFactory>`, can move along with its statement.
                $movableComments = $comments === [] || (count($comments) === 1 && $tokens[$comments[0]]->isGivenKind(T_DOC_COMMENT));
                if ($range === null || $movableComments === false) {
                    continue 2;
                }

                $ranges[] = [$comments[0] ?? null, ...$range];
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
     * @param Tokens<Token> $tokens
     * @param int $start
     *
     * @return array{int, int}|null
     */
    protected function simpleStatementRange(Tokens $tokens, int $start): ?array
    {
        // A `{ ... }` conflict resolution block or a comment can't survive the merge, so the class is left alone.
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
     * @param int $start
     *
     * @return list<int>
     */
    protected function leadingComments(Tokens $tokens, int $start): array
    {
        $comments = [];
        $index = $tokens->getPrevNonWhitespace($start);
        while ($index !== null && $tokens[$index]->isComment()) {
            $comments[] = $index;
            $index = $tokens->getPrevNonWhitespace($index);
        }

        return $comments;
    }

    /**
     * @param Tokens<Token> $tokens
     * @param list<array{int|null, int, int}> $ranges
     *
     * @return void
     */
    protected function merge(Tokens $tokens, array $ranges): void
    {
        $names = collect($ranges)
            ->flatMap(fn(array $range): array => $this->names($tokens, $range[1], $range[2]))
            ->all();

        $docblocks = [];
        foreach ($ranges as [$docblock]) {
            if ($docblock !== null) {
                $docblocks[] = $tokens[$docblock]->getContent();
            }
        }

        // Later statements first, so the first statement's indexes stay put until it is rewritten.
        foreach (collect($ranges)->skip(1)->reverse() as [$docblock, $start, $end]) {
            $from = $docblock ?? $start;
            $tokens->clearRange($from, $end);
            $tokens->removeLeadingWhitespace($from);
        }

        [$firstDocblock, $firstStart, $firstEnd] = $ranges[0];
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
        $this->placeDocblock($tokens, $firstDocblock, $firstStart, $docblocks);
    }

    /**
     * PHPStan reads only the docblock right above a statement, so every `@use` tag has to end up in that one.
     *
     * @param Tokens<Token> $tokens
     * @param int|null $firstDocblock
     * @param int $firstStart
     * @param list<string> $docblocks
     *
     * @return void
     */
    protected function placeDocblock(Tokens $tokens, ?int $firstDocblock, int $firstStart, array $docblocks): void
    {
        if ($docblocks === []) {
            return;
        }

        $indent = $tokens[$firstStart - 1]->isWhitespace() ? Str::afterLast($tokens[$firstStart - 1]->getContent(), "\n") : '';
        $docblock = count($docblocks) === 1 ? $docblocks[0] : $this->combineDocblocks($docblocks, $indent);

        if ($firstDocblock !== null) {
            $tokens[$firstDocblock] = new Token([T_DOC_COMMENT, $docblock]);

            return;
        }

        $tokens->insertAt($firstStart, [new Token([T_DOC_COMMENT, $docblock]), new Token([T_WHITESPACE, "\n{$indent}"])]);
    }

    /**
     * @param list<string> $docblocks
     * @param string $indent
     *
     * @return string
     */
    protected function combineDocblocks(array $docblocks, string $indent): string
    {
        $lines = collect($docblocks)
            ->flatMap(fn(string $docblock): array => Str::of($docblock)->after('/**')->beforeLast('*/')->explode("\n")->all())
            ->map(fn(string $line): string => Str::of($line)->trim()->ltrim('*')->trim()->toString())
            ->filter()
            ->map(fn(string $line): string => "{$indent} * {$line}\n")
            ->implode('');

        return "/**\n{$lines}{$indent} */";
    }

    /**
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
