<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Config\Diff;

use Illuminate\Support\Str;

class DiffLine
{
    /**
     * @param DiffOperation $operation
     * @param string $text
     */
    public function __construct(
        public readonly DiffOperation $operation,
        public readonly string $text,
    ) {}

    /**
     * @param string $line
     *
     * @return self|null
     */
    public static function parse(string $line): self|null
    {
        $operation = DiffOperation::fromPrefix(Str::substr($line, 0, 1));
        if ($operation === null) {
            return null;
        }

        return new self($operation, Str::substr($line, 1));
    }

    /**
     * @return string
     */
    public function toDiff(): string
    {
        return "{$this->operation->prefix()}{$this->text}";
    }
}
