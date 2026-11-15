<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\Rector\RectorConfigFactory;
use AdamczykPiotr\UltimateFormatter\Rector\Rules\SingleLineClosureToArrowFunctionRector;
use PHPUnit\Framework\TestCase;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use Symfony\Component\Process\Process;

class RectorConfigFactoryTest extends TestCase
{
    /**
     * Rector's `list-rules` takes a few seconds, so its output is shared by the tests below.
     *
     * @var list<string>|null
     */
    private static ?array $loadedRules = null;

    /**
     * @return void
     */
    public function test_loads_the_rules_of_the_standard(): void
    {
        $loadedRules = self::loadedRules();

        $this->assertContains(ClassPropertyAssignToConstructorPromotionRector::class, $loadedRules);
        $this->assertContains(ChangeSwitchToMatchRector::class, $loadedRules);
        $this->assertContains(SingleLineClosureToArrowFunctionRector::class, $loadedRules);
        $this->assertContains(RemoveDumpDataDeadCodeRector::class, $loadedRules);
        $this->assertContains('Rector\Carbon\Rector\FuncCall\DateFuncCallToCarbonRector', $loadedRules);
        $this->assertContains('Rector\Carbon\Rector\New_\DateTimeInstanceToCarbonRector', $loadedRules);
    }

    /**
     * @return void
     */
    public function test_never_loads_rules_that_conflict_with_the_standard(): void
    {
        $loadedRules = self::loadedRules();

        // Job properties stay protected for queue serialization.
        $this->assertNotContains(PrivatizeFinalClassPropertyRector::class, $loadedRules);
        // Strings are interpolated, never built with sprintf(). Named as a string, since the rule is deprecated.
        $this->assertNotContains('Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector', $loadedRules);
        // Squashes multi-line closures into long arrow functions; the single-line variant replaces it.
        $this->assertNotContains(ClosureToArrowFunctionRector::class, $loadedRules);
    }

    /**
     * @return void
     */
    public function test_skips_the_validate_and_create_rename_without_laravel_data(): void
    {
        $this->assertArrayNotHasKey(RenameMethodRector::class, RectorConfigFactory::configuredRules(laravelDataInstalled: false));
    }

    /**
     * Asks Rector itself which rules the factory loads, so rules pulled in by sets count too.
     *
     * @return list<string>
     */
    private static function loadedRules(): array
    {
        if (self::$loadedRules !== null) {
            return self::$loadedRules;
        }

        $root = dirname(__DIR__, 2);
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/rector', 'list-rules', '--output-format=json', '--config', __DIR__ . '/../Rector/RectorConfigFactory/config/factory.php'],
            $root,
        );
        $process->mustRun();

        /** @var array{rectors: list<string>} $output */
        $output = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        return self::$loadedRules = $output['rectors'];
    }
}
