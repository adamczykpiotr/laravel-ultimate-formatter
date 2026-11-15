<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\IgnoreMarkers;

/**
 * A run of lines that a fixer either left alone or changed.
 */
class Hunk
{
    /**
     * @param list<string> $old The lines before the fixer ran.
     * @param list<string> $new The lines after the fixer ran.
     * @param int $start The first line of $old, 1-based.
     * @param bool $changed
     */
    public function __construct(
        public array $old,
        public array $new,
        public readonly int $start,
        public readonly bool $changed,
    ) {}

    /**
     * @param array<int, true> $protected
     *
     * @return bool True when the hunk changes a protected line, or adds lines next to one.
     */
    public function touches(array $protected): bool
    {
        if ($this->changed === false) {
            return false;
        }

        $lines = $this->old === []
            ? [$this->start - 1, $this->start]
            : range($this->start, $this->start + count($this->old) - 1);

        return collect($lines)->contains(fn(int $line): bool => isset($protected[$line]));
    }

    /**
     * A hunk that rewrites as many lines as it replaces is a run of single-line edits, like a literal fixed on each
     * of two adjacent lines. Each edit then stands on its own, so a protected line keeps only its own one.
     *
     * @return list<self>
     */
    public function split(): array
    {
        if ($this->changed === false || count($this->old) !== count($this->new)) {
            return [$this];
        }

        $hunks = [];
        foreach ($this->old as $offset => $old) {
            $hunks[] = new self([$old], [$this->new[$offset]], $this->start + $offset, true);
        }

        return $hunks;
    }
}
