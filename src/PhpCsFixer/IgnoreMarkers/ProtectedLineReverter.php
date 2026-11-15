<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\IgnoreMarkers;

use Illuminate\Support\Collection;
use ParseError;
use PhpToken;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

class ProtectedLineReverter
{
    /**
     * Blank lines and lone brackets, which any two hunks may share without a line having moved.
     */
    protected const SHARED_LINES = ['', '{', '}', '(', ')', '[', ']', '];', ');', '},', '],'];

    /**
     * Undoes one fixer's changes to protected lines and keeps the rest. A change that only adds lines counts as
     * touching a protected line next to it.
     *
     * @param string $before The code before the fixer ran.
     * @param string $after The code after the fixer ran.
     * @param array<int, true> $protected The protected lines of $before, 1-based.
     *
     * @return string
     */
    public function revert(string $before, string $after, array $protected): string
    {
        if ($before === $after || $protected === []) {
            return $after;
        }

        $hunks = $this->hunks(explode("\n", $before), explode("\n", $after));
        $reverted = $hunks->filter(fn(Hunk $hunk): bool => $hunk->touches($protected));
        $applied = $hunks->filter(fn(Hunk $hunk): bool => $hunk->changed)->diffKeys($reverted);
        if ($reverted->isEmpty()) {
            return $after;
        }

        // A fixer that moves a line, like ordered_imports, removes it in one hunk and adds it in another. Keeping
        // only one half would lose or duplicate the line, so the fixer is undone for the whole file instead.
        $movedOut = $this->lines($reverted, 'old')->intersect($this->lines($applied, 'new'));
        $movedIn = $this->lines($reverted, 'new')->intersect($this->lines($applied, 'old'));
        if ($movedOut->isNotEmpty() || $movedIn->isNotEmpty()) {
            return $before;
        }

        $code = $hunks
            ->flatMap(fn(Hunk $hunk): array => $hunk->touches($protected) ? $hunk->old : $hunk->new)
            ->implode("\n");

        // Half of a change that spans several hunks may not parse on its own.
        try {
            PhpToken::tokenize($code, TOKEN_PARSE);
        } catch (ParseError) {
            return $before;
        }

        return $code;
    }

    /**
     * @param list<string> $old
     * @param list<string> $new
     *
     * @return Collection<int, Hunk>
     */
    protected function hunks(array $old, array $new): Collection
    {
        /** @var list<Hunk> $hunks */
        $hunks = [];
        $line = 1;
        $open = null;

        /** @var list<array{0: string, 1: int}> $diff */
        $diff = (new Differ(new UnifiedDiffOutputBuilder()))->diffToArray($old, $new);
        foreach ($diff as [$text, $type]) {
            if ($type === Differ::OLD) {
                $hunks[] = new Hunk([$text], [$text], $line, false);
                $open = null;
                $line++;

                continue;
            }

            if ($type !== Differ::REMOVED && $type !== Differ::ADDED) {
                continue;
            }

            if ($open === null) {
                $open = new Hunk([], [], $line, true);
                $hunks[] = $open;
            }

            if ($type === Differ::REMOVED) {
                $open->old[] = $text;
                $line++;
            } else {
                $open->new[] = $text;
            }
        }

        return collect($hunks)->flatMap(fn(Hunk $hunk): array => $hunk->split())->values();
    }

    /**
     * The lines one side of the hunks holds, without the lines any two hunks may share.
     *
     * @param Collection<int, Hunk> $hunks
     * @param 'old'|'new' $side
     *
     * @return Collection<int, string>
     */
    protected function lines(Collection $hunks, string $side): Collection
    {
        return $hunks
            ->flatMap(fn(Hunk $hunk): array => $hunk->{$side})
            ->map(fn(string $line): string => trim($line))
            ->reject(fn(string $line): bool => in_array($line, self::SHARED_LINES, true))
            ->unique()
            ->values();
    }
}
