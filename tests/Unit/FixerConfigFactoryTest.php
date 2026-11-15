<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\PhpCsFixer\FixerConfigFactory;
use Fidry\CpuCoreCounter\CpuCoreCounter;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class FixerConfigFactoryTest extends TestCase
{
    /**
     * @return void
     */
    public function test_creates_risky_config_with_shared_rules(): void
    {
        $config = FixerConfigFactory::create(Finder::create(), 'build/.php-cs-fixer.cache');

        $this->assertTrue($config->getRiskyAllowed());
        $this->assertSame('build/.php-cs-fixer.cache', $config->getCacheFile());
        $this->assertSame(FixerConfigFactory::rules(), $config->getRules());
    }

    /**
     * @return void
     */
    public function test_runs_in_parallel_on_every_available_core(): void
    {
        $cores = (new CpuCoreCounter())->getAvailableForParallelisation();

        if ($cores->correctedCountLimit !== null) {
            $this->markTestSkipped('A CPU quota caps the core count on this machine.');
        }

        $config = FixerConfigFactory::create(Finder::create(), 'build/.php-cs-fixer.cache');

        $this->assertSame($cores->totalCoresCount, $config->getParallelConfig()->getMaxProcesses());
    }

    /**
     * @return void
     */
    public function test_rules_are_known_and_not_deprecated(): void
    {
        $fixerFactory = new FixerFactory();
        $fixerFactory->registerBuiltInFixers();
        $fixerFactory->registerCustomFixers(FixerConfigFactory::customFixers());
        $fixerFactory->useRuleSet(new RuleSet(FixerConfigFactory::rules()));

        $deprecatedFixers = collect($fixerFactory->getFixers())
            ->filter(fn(FixerInterface $fixer): bool => $fixer instanceof DeprecatedFixerInterface)
            ->map(fn(FixerInterface $fixer): string => $fixer->getName());

        $this->assertSame([], $deprecatedFixers->values()->all());
    }

    /**
     * @return void
     */
    public function test_never_enables_fixers_that_conflict_with_the_standard(): void
    {
        $enabledFixers = collect($this->fixers(FixerConfigFactory::rules()))
            ->map(fn(FixerInterface $fixer): string => $fixer->getName());

        $this->assertNotContains('single_trait_insert_per_statement', $enabledFixers);
        // Rewrites hex, octal, binary and exponent literals; UltimateFormatter/numeric_literal_separator replaces it.
        $this->assertNotContains('numeric_literal_separator', $enabledFixers);
    }

    /**
     * @return void
     */
    public function test_sorts_traits_into_one_statement_first_in_the_class(): void
    {
        $fixers = $this->fixers([
            'UltimateFormatter/single_trait_use_statement' => true,
            'ordered_traits' => FixerConfigFactory::rules()['ordered_traits'],
            'ordered_class_elements' => FixerConfigFactory::rules()['ordered_class_elements'],
        ]);

        Tokens::clearCache();
        $tokens = Tokens::fromCode(
            "<?php\nclass Tag\n{\n    use SoftDeletes;\n    public const TYPE = 'tag';\n    use Notifiable, HasFactory;\n    public function name(): string\n    {\n        return 'tag';\n    }\n}\n",
        );
        foreach ($fixers as $fixer) {
            $fixer->fix(new SplFileInfo(__FILE__), $tokens);
        }

        $this->assertSame(
            "<?php\nclass Tag\n{\n    use HasFactory, Notifiable, SoftDeletes;\n    public const TYPE = 'tag';\n    public function name(): string\n    {\n        return 'tag';\n    }\n}\n",
            $tokens->generateCode(),
        );
    }

    /**
     * @return void
     */
    public function test_enforces_strict_types_and_next_line_braces(): void
    {
        $rules = FixerConfigFactory::rules();

        $this->assertTrue($rules['declare_strict_types']);
        $this->assertSame(
            'next_line_unless_newline_at_signature_end',
            data_get($rules, 'braces_position.functions_opening_brace'),
        );
    }

    /**
     * @param array<string, array<string, mixed>|bool> $rules
     *
     * @return list<FixerInterface>
     */
    private function fixers(array $rules): array
    {
        $fixerFactory = new FixerFactory();
        $fixerFactory->registerBuiltInFixers();
        $fixerFactory->registerCustomFixers(FixerConfigFactory::customFixers());
        $fixerFactory->useRuleSet(new RuleSet($rules));

        return $fixerFactory->getFixers();
    }
}
