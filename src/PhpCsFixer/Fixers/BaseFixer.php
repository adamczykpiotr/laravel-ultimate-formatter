<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers;

use PhpCsFixer\Fixer\FixerInterface;
use SplFileInfo;

abstract class BaseFixer implements FixerInterface
{
    /**
     * @return bool
     */
    public function isRisky(): bool
    {
        return false;
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
}
