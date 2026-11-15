<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Drift;

use AdamczykPiotr\UltimateFormatter\PhpCsFixer\FixerConfigFactory;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PHPUnit\Framework\Attributes\Group;
use Rector\Configuration\Deprecation\Contract\DeprecatedInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * A rule that is added, removed or deprecated upstream shows up as a diff of the snapshot.
 * Rector only warns about a deprecated rule that is registered, and says nothing when one drops out of a set.
 */
#[Group('drift')]
class RuleInventoryTest extends DriftTestCase
{
    protected const SNAPSHOT_FILE = __DIR__ . '/Snapshot/rules.txt';
    protected const DEPRECATED_MARKER = ' (deprecated)';

    /**
     * @return void
     */
    public function test_resolved_rules_match_the_snapshot(): void
    {
        $inventory = implode("\n", [
            '# Rector rules, after the sets are expanded',
            ...$this->rectorRules(),
            '',
            '# PHP CS Fixer rules, with every option resolved',
            ...$this->fixerRules(),
            '',
            '# PHPStan rules at level max, with phpstan-rules.neon and the required extensions',
            ...$this->phpstanRules(),
            '',
        ]);

        if ($this->updatesFixtures()) {
            (new Filesystem())->dumpFile(self::SNAPSHOT_FILE, $inventory);
        }

        $this->assertStringEqualsFile(self::SNAPSHOT_FILE, $inventory);
    }

    /**
     * @return array<int, string>
     */
    protected function rectorRules(): array
    {
        $output = $this->runPhp(['vendor/bin/rector', 'list-rules', '--output-format=json', '--config=' . __DIR__ . '/config/rector.php']);

        /** @var array{rectors: list<class-string>, skipped-rectors: list<class-string>} $rules */
        $rules = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        $skipped = collect($rules['skipped-rectors'])->map(fn(string $rule): string => "{$rule} (skipped)");

        return collect($rules['rectors'])
            ->map(fn(string $rule): string => is_a($rule, DeprecatedInterface::class, true) ? $rule . self::DEPRECATED_MARKER : $rule)
            ->merge($skipped)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function fixerRules(): array
    {
        $rules = FixerConfigFactory::rules();
        $fixers = (new FixerFactory())
            ->registerBuiltInFixers()
            ->registerCustomFixers(FixerConfigFactory::customFixers())
            ->useRuleSet(new RuleSet($rules))
            ->getFixers();

        return collect($fixers)
            ->map(fn(FixerInterface $fixer): string => $fixer->getName()
                . $this->fixerConfiguration($fixer, $rules[$fixer->getName()] ?? true)
                . ($fixer instanceof DeprecatedFixerInterface ? self::DEPRECATED_MARKER : ''))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Resolving the defaults too means an upstream change to a default option shows up in the snapshot.
     *
     * @param FixerInterface $fixer
     * @param array<string, mixed>|bool $configuration
     *
     * @return string
     */
    protected function fixerConfiguration(FixerInterface $fixer, array|bool $configuration): string
    {
        if ($fixer instanceof ConfigurableFixerInterface === false) {
            return '';
        }

        $resolved = $fixer->getConfigurationDefinition()->resolve(is_array($configuration) ? $configuration : []);
        ksort($resolved);

        return ' ' . json_encode($resolved, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, string>
     */
    protected function phpstanRules(): array
    {
        $output = $this->runPhp([__DIR__ . '/dump-phpstan-rules.php']);

        return collect(explode("\n", trim($output)))->values()->all();
    }

    /**
     * @param list<string> $arguments
     *
     * @return string
     */
    protected function runPhp(array $arguments): string
    {
        $process = new Process([PHP_BINARY, ...$arguments], self::ROOT_DIRECTORY, timeout: null);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), "{$process->getCommandLine()} failed:\n{$process->getOutput()}{$process->getErrorOutput()}");

        return $process->getOutput();
    }
}
