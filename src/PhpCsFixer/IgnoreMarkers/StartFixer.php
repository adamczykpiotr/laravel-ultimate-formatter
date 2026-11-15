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
 * Runs before every other rule and hands each file to the IgnoreMarkerPolicy.
 */
class StartFixer implements FixerInterface
{
    public const NAME = 'UltimateFormatter/ignore_markers';

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
        $this->policy->start($file, $tokens);
    }

    /**
     * @return FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Reads the `// formatter-ignore <rule>` markers of the file.', []);
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
        return PHP_INT_MAX;
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
