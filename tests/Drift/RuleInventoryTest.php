<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Drift;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Rector\Configuration\Deprecation\Contract\DeprecatedInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Rector warns about a deprecated rule only when it is registered, and says nothing when one drops out of a set.
 * Snapshotting the rules after the sets are expanded turns both into a diff.
 */
#[Group('drift')]
class RuleInventoryTest extends TestCase
{
    protected const SNAPSHOT_FILE = __DIR__ . '/Snapshot/rector-rules.txt';

    /**
     * @return void
     */
    public function test_resolved_rector_rules_match_the_snapshot(): void
    {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/rector', 'list-rules', '--output-format=json', '--config=' . __DIR__ . '/config/rector.php'],
            __DIR__ . '/../..',
            timeout: null,
        );
        $process->mustRun();

        /** @var array{rectors: list<class-string>} $rules */
        $rules = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $snapshot = collect($rules['rectors'])
            ->map(fn(string $rule): string => is_a($rule, DeprecatedInterface::class, true) ? "{$rule} (deprecated)" : $rule)
            ->sort()
            ->implode("\n") . "\n";

        if (getenv('UPDATE_FIXTURES') === '1') {
            (new Filesystem())->dumpFile(self::SNAPSHOT_FILE, $snapshot);
        }

        $this->assertStringEqualsFile(self::SNAPSHOT_FILE, $snapshot);
    }
}
