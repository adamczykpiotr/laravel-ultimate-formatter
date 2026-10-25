<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\PhpCsFixer\FixerConfigFactory;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PHPUnit\Framework\TestCase;

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
    public function test_enforces_strict_types_and_next_line_braces(): void
    {
        $rules = FixerConfigFactory::rules();

        $this->assertTrue($rules['declare_strict_types']);
        $this->assertSame(
            'next_line_unless_newline_at_signature_end',
            data_get($rules, 'braces_position.functions_opening_brace'),
        );
    }
}
