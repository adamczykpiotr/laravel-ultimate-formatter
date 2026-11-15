<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer\IgnoreMarkers;

use AdamczykPiotr\UltimateFormatter\Ignore\IgnoreMarkers;
use Closure;
use PhpCsFixer\Config\RuleCustomisationPolicyInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use UnexpectedValueException;

/**
 * Applies `// formatter-ignore <rule>` markers line by line. PHP CS Fixer only lets a policy skip a rule for a whole
 * file, but it calls the policy right before each rule runs, on the one Tokens collection every rule of the file
 * shares. So before each rule, this undoes the previous rule's changes to the lines its markers protect. The
 * StartFixer hands it the collection, and the EndFixer settles the last rule.
 */
class IgnoreMarkerPolicy implements RuleCustomisationPolicyInterface
{
    /**
     * @var Tokens<Token>|null Set only while a file with markers is being fixed.
     */
    protected ?Tokens $tokens = null;

    protected string $file = '';
    protected ?string $rule = null;
    protected string $before = '';

    /**
     * @param list<string> $rules The enabled rules, without the two guard fixers.
     * @param ProtectedLineReverter $reverter
     */
    public function __construct(
        protected readonly array $rules,
        protected readonly ProtectedLineReverter $reverter = new ProtectedLineReverter(),
    ) {}

    /**
     * @return non-empty-string
     */
    public function getPolicyVersionForCache(): string
    {
        $sources = collect([__FILE__, __DIR__ . '/ProtectedLineReverter.php', __DIR__ . '/../../Ignore/IgnoreMarkers.php'])
            ->map(fn(string $file): string => (string)file_get_contents($file))
            ->implode('');

        return hash('xxh128', $sources);
    }

    /**
     * @return array<string, Closure(SplFileInfo): bool>
     */
    public function getRuleCustomisers(): array
    {
        return collect($this->rules)
            ->mapWithKeys(fn(string $rule): array => [$rule => fn(SplFileInfo $file): bool => $this->beforeRule($rule, $file)])
            ->all();
    }

    /**
     * @param SplFileInfo $file
     * @param Tokens<Token> $tokens
     *
     * @return void
     */
    public function start(SplFileInfo $file, Tokens $tokens): void
    {
        $this->tokens = null;
        $this->rule = null;

        if ($tokens->isTokenKindFound(T_COMMENT) === false) {
            return;
        }

        $markers = IgnoreMarkers::fromCode($tokens->generateCode());
        if ($markers->isEmpty()) {
            return;
        }

        $this->validate($markers);
        $this->tokens = $tokens;
        $this->file = $file->getPathname();
    }

    /**
     * @param SplFileInfo $file
     *
     * @return void
     */
    public function finish(SplFileInfo $file): void
    {
        if ($this->tokens !== null && $file->getPathname() === $this->file) {
            $this->settle($this->tokens);
        }

        $this->tokens = null;
        $this->rule = null;
    }

    /**
     * @param string $rule
     * @param SplFileInfo $file
     *
     * @return bool
     */
    protected function beforeRule(string $rule, SplFileInfo $file): bool
    {
        $tokens = $this->tokens;
        if ($tokens === null || $file->getPathname() !== $this->file) {
            return true;
        }

        $this->settle($tokens);
        $this->rule = $rule;
        $this->before = $tokens->generateCode();

        return true;
    }

    /**
     * @param Tokens<Token> $tokens
     *
     * @return void
     */
    protected function settle(Tokens $tokens): void
    {
        $rule = $this->rule;
        $this->rule = null;

        $after = $tokens->generateCode();
        if ($rule === null || $after === $this->before) {
            return;
        }

        // Line numbers are read from the code the rule saw, as earlier rules may have added or removed lines.
        $protected = IgnoreMarkers::fromCode($this->before)->protectedLines($rule);
        $code = $this->reverter->revert($this->before, $after, $protected);
        if ($code === $after) {
            return;
        }

        $tokens->setCode($code);
        $tokens->clearChanged();
    }

    /**
     * @param IgnoreMarkers $markers
     *
     * @return void
     */
    protected function validate(IgnoreMarkers $markers): void
    {
        foreach ($markers->errors as $line => $error) {
            throw new UnexpectedValueException("Line {$line}: {$error}");
        }

        $unknown = collect($markers->identifiers())
            ->filter(fn(string $identifier): bool => IgnoreMarkers::isFixerIdentifier($identifier))
            ->diff($this->rules);

        if ($unknown->isNotEmpty()) {
            throw new UnexpectedValueException("A formatter-ignore marker names rules that are not enabled: {$unknown->implode(', ')}.");
        }
    }
}
