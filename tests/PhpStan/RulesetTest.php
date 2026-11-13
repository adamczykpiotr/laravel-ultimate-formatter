<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\PhpStan;

use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RulesetTest extends TestCase
{
    protected const FIXTURE_DIRECTORY = __DIR__ . '/Fixture';

    /**
     * @return void
     */
    public function test_reports_every_banned_call_and_architecture_violation(): void
    {
        $this->assertSame([
            'app/Dto/Tag/Input/Fields/TagIcon.php:7 phpat.testInputDtosAreNamedInput',
            'app/Dto/Tag/Input/Fields/TagIcon.php:7 phpat.testInputDtosExtendBaseInput',
            'app/Dto/Tag/Input/TagRename.php:7 phpat.testInputDtosAreNamedInput',
            'app/Dto/Tag/Input/TagRename.php:7 phpat.testInputDtosExtendBaseInput',
            'app/Dto/Tag/TagSummary.php:7 phpat.testOutputDtosAreNamedDto',
            'app/Exceptions/TagLocked.php:9 phpat.testExceptionsAreNamedException',
            'app/Exceptions/TagLocked.php:9 phpat.testExceptionsExtendBaseException',
            'app/Http/Controllers/TagController.php:13 ultimateFormatter.disallowResponseJson',
            'app/Http/Controllers/TagHandler.php:7 phpat.testControllersAreNamedController',
            'app/Http/Requests/TagStoreRequest.php:9 phpat.testNoClassExtendsFormRequest',
            'app/Http/Resources/TagPayload.php:9 phpat.testResourcesAreNamedResource',
            'app/Services/TagExporter.php:7 phpat.testServicesAreFinal',
            'app/Services/TagExporter.php:7 phpat.testServicesAreReadonly',
            'app/Services/TagOwnerService.php:13 phpat.testServicesDoNotDependOnRequestResponse',
            'app/Services/TagService.php:15 phpat.testServicesDoNotDependOnRequestResponse',
            'app/Services/TagService.php:17 ultimateFormatter.disallowRequestResponseHelpersInServices',
            'app/Services/TagService.php:17 ultimateFormatter.disallowSprintf',
            'app/Services/TagService.php:18 ultimateFormatter.disallowNativeArrayFunctions',
            'app/Services/TagService.php:19 ultimateFormatter.disallowFileExists',
            'app/Services/TagService.php:19 ultimateFormatter.disallowMethodExists',
            'app/Services/TagService.php:19 ultimateFormatter.disallowNativePregFunctions',
            'app/Services/TagService.php:20 ultimateFormatter.disallowRequestResponseHelpersInServices',
            'app/Services/TagService.php:21 ultimateFormatter.disallowRequestResponseHelpersInServices',
            'app/Services/TagService.php:22 larastan.noEnvCallsOutsideOfConfig',
            'app/Services/TagService.php:23 ultimateFormatter.disallowRequestResponseHelpersInServices',
            'app/Services/TagService.php:24 phpat.testServicesDoNotDependOnRequestResponse',
            'app/Services/TagService.php:26 ultimateFormatter.disallowModelMassAssignment',
            'app/Services/TagService.php:27 ultimateFormatter.disallowModelMassAssignment',
            'app/Services/TagService.php:28 ultimateFormatter.disallowModelMassAssignment',
            'app/Services/TagService.php:29 ultimateFormatter.disallowModelMassAssignment',
            'app/Services/TagService.php:30 ultimateFormatter.disallowModelMassAssignment',
            'app/Services/TagService.php:31 ultimateFormatter.disallowModelMassAssignment',
            'app/Services/TagService.php:34 ultimateFormatter.disallowAppEnvironment',
            'database/migrations/2026_01_01_000000_create_tags_table.php:12 phpat.testMigrationsDoNotDependOnModels',
            'tests/TagServiceTest.php:15 ultimateFormatter.disallowLogShouldReceive',
            'tests/TagServiceTest.php:19 ultimateFormatter.disallowAppEnvironment',
        ], $this->analyseFixtures());
    }

    /**
     * @return void
     */
    public function test_rule_flags_turn_rules_off_and_on_between_runs(): void
    {
        $directory = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-flags-', true);
        $filesystem = new Filesystem();
        $filesystem->dumpFile("{$directory}/phpstan.neon", implode("\n", [
            'includes:',
            '    - ' . self::FIXTURE_DIRECTORY . '/phpstan.neon',
            'parameters:',
            "    ultimateFormatterConfigFile: {$directory}/ultimate-formatter.php",
            '',
        ]));

        try {
            $filesystem->dumpFile("{$directory}/ultimate-formatter.php", "<?php\n\nreturn ['phpstan-rules' => ['phpat.testServicesAreNamedService' => true, 'phpat.testServicesAreFinal' => false, 'ultimateFormatter.disallowSprintf' => false]];\n");
            $flipped = $this->analyseFixtures("{$directory}/phpstan.neon");

            // Same config and result cache: the second run must not reuse the first one's filtering.
            $filesystem->dumpFile("{$directory}/ultimate-formatter.php", "<?php\n\nreturn ['phpstan-rules' => []];\n");
            $defaults = $this->analyseFixtures("{$directory}/phpstan.neon");
        } finally {
            $filesystem->remove($directory);
        }

        $this->assertContains('app/Services/TagExporter.php:7 phpat.testServicesAreNamedService', $flipped);
        $this->assertNotContains('app/Services/TagExporter.php:7 phpat.testServicesAreFinal', $flipped);
        $this->assertContains('app/Services/TagExporter.php:7 phpat.testServicesAreReadonly', $flipped);
        $this->assertNotContains('app/Services/TagService.php:17 ultimateFormatter.disallowSprintf', $flipped);
        $this->assertContains('app/Services/TagService.php:18 ultimateFormatter.disallowNativeArrayFunctions', $flipped);

        $this->assertNotContains('app/Services/TagExporter.php:7 phpat.testServicesAreNamedService', $defaults);
        $this->assertContains('app/Services/TagExporter.php:7 phpat.testServicesAreFinal', $defaults);
        $this->assertContains('app/Services/TagService.php:17 ultimateFormatter.disallowSprintf', $defaults);
    }

    /**
     * @return void
     */
    public function test_an_unknown_rule_flag_stops_the_run_before_the_analysis(): void
    {
        $directory = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-flags-', true);
        $filesystem = new Filesystem();
        $filesystem->dumpFile("{$directory}/phpstan.neon", implode("\n", [
            'includes:',
            '    - ' . self::FIXTURE_DIRECTORY . '/phpstan.neon',
            'parameters:',
            "    ultimateFormatterConfigFile: {$directory}/ultimate-formatter.php",
            '',
        ]));
        $filesystem->dumpFile("{$directory}/ultimate-formatter.php", "<?php\n\nreturn ['phpstan-rules' => ['ultimateFormatter.disallowSprintF' => false]];\n");

        try {
            $process = new Process(
                [PHP_BINARY, 'vendor/bin/phpstan', 'analyse', "--configuration={$directory}/phpstan.neon", '--no-progress'],
                __DIR__ . '/../..',
                timeout: null,
            );
            $process->run();
        } finally {
            $filesystem->remove($directory);
        }

        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('has unknown rules: ultimateFormatter.disallowSprintF.', $process->getOutput() . $process->getErrorOutput());
        $this->assertStringNotContainsString('Internal error', $process->getOutput() . $process->getErrorOutput());
    }

    /**
     * @param string $configFile
     *
     * @return array<int, string>
     */
    protected function analyseFixtures(string $configFile = self::FIXTURE_DIRECTORY . '/phpstan.neon'): array
    {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/phpstan', 'analyse', "--configuration={$configFile}", '--error-format=json', '--no-progress'],
            __DIR__ . '/../..',
            timeout: null,
        );
        $process->run();

        /** @var array{files: array<string, array{messages: list<array{line: int, identifier: string}>}>} $result */
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        return collect($result['files'])
            ->flatMap(fn(array $file, string $path): array => collect($file['messages'])
                ->map(fn(array $message): string => str_replace(self::FIXTURE_DIRECTORY . '/', '', $path) . ":{$message['line']} {$message['identifier']}")
                ->all())
            ->sort()
            ->values()
            ->all();
    }
}
