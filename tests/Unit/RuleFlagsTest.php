<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\PhpStan\RuleFlags;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

class RuleFlagsTest extends TestCase
{
    protected const ROOT_DIRECTORY = __DIR__ . '/../..';

    protected string $configFile;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configFile = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-flags-', true) . '.php';
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->configFile);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_default_flags_list_every_rule_the_ruleset_reports(): void
    {
        $neon = (string)file_get_contents(self::ROOT_DIRECTORY . '/phpstan-rules.neon');
        preg_match_all("/errorIdentifier: '([^']+)'/", $neon, $bannedCalls);

        $customRules = collect(glob(self::ROOT_DIRECTORY . '/src/PhpStan/Rules/*.php') ?: [])
            ->flatMap(function (string $file): array {
                preg_match_all("/->identifier\\('([^']+)'\\)/", (string)file_get_contents($file), $matches);

                return $matches[1];
            });

        $architectureRules = collect(glob(self::ROOT_DIRECTORY . '/src/PhpStan/Architecture/*.php') ?: [])
            ->flatMap(function (string $file): array {
                $class = 'AdamczykPiotr\\UltimateFormatter\\PhpStan\\Architecture\\' . basename($file, '.php');
                $this->assertTrue(class_exists($class));

                return (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC);
            })
            ->map(fn(ReflectionMethod $method): string => $method->getName())
            ->filter(fn(string $method): bool => str_starts_with($method, 'test'))
            ->map(fn(string $method): string => "phpat.{$method}");

        // The one Larastan rule the ruleset turns on, through its noEnvCallsOutsideOfConfig parameter.
        $identifiers = collect($bannedCalls[1])
            ->merge($customRules)
            ->merge($architectureRules)
            ->push('larastan.noEnvCallsOutsideOfConfig')
            ->unique()
            ->sort()
            ->values()
            ->all();

        /** @var array{phpstan-rules: array<string, bool>} $config */
        $config = require self::ROOT_DIRECTORY . '/config/ultimate-formatter.php';
        $flags = collect($config['phpstan-rules'])->keys()->sort()->values()->all();

        $this->assertSame($identifiers, $flags);
    }

    /**
     * @return void
     */
    public function test_accepts_flags_for_the_ruleset_rules(): void
    {
        $this->writeConfig("['phpstan-rules' => ['ultimateFormatter.disallowSprintf' => false, 'phpat.testServicesAreNamedService' => true]]");
        $this->expectNotToPerformAssertions();

        new RuleFlags($this->configFile);
    }

    /**
     * @return void
     */
    public function test_accepts_a_missing_config_file(): void
    {
        $this->expectNotToPerformAssertions();

        new RuleFlags($this->configFile);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidConfigs(): array
    {
        return [
            'misspelled rule' => [
                "['phpstan-rules' => ['ultimateFormatter.disallowSprintF' => false]]",
                'has unknown rules: ultimateFormatter.disallowSprintF.',
            ],
            'error identifier from outside the ruleset' => [
                "['phpstan-rules' => ['argument.type' => false]]",
                'has unknown rules: argument.type.',
            ],
            'value that is not a boolean' => [
                "['phpstan-rules' => ['phpat.testServicesAreFinal' => 0]]",
                'phpstan-rules.phpat.testServicesAreFinal in',
            ],
            'rule listed without a value' => [
                "['phpstan-rules' => ['phpat.testServicesAreFinal']]",
                'phpstan-rules.0 in',
            ],
            'flags that are not an array' => [
                "['phpstan-rules' => true]",
                'must map rule identifiers to booleans.',
            ],
        ];
    }

    /**
     * @param string $config
     * @param string $message
     *
     * @return void
     */
    #[DataProvider('invalidConfigs')]
    public function test_rejects_flags_it_cannot_apply(string $config, string $message): void
    {
        $this->writeConfig($config);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        new RuleFlags($this->configFile);
    }

    /**
     * @param string $config
     *
     * @return void
     */
    protected function writeConfig(string $config): void
    {
        (new Filesystem())->dumpFile($this->configFile, "<?php\n\nreturn {$config};\n");
    }
}
