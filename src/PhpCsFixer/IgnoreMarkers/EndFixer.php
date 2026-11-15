<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\IgnoreMarkers;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Runs after every other rule, so the IgnoreMarkerPolicy can undo the last rule's changes to protected lines.
 */
class EndFixer implements FixerInterface
{
    public const NAME = 'UltimateFormatter/ignore_markers_end';

    /**
     * @param IgnoreMarkerPolicy $policy
     */
    public function __construct(
        protected readonly IgnoreMarkerPolicy $policy,
    ) {}

    /**
     * @param Tokens<Token> $tokens
     *
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return true;
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
        $this->policy->finish($file);
    }

    /**
     * @return FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Undoes the last rule\'s changes to lines that `// formatter-ignore <rule>` markers protect.', []);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return PHP_INT_MIN;
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
