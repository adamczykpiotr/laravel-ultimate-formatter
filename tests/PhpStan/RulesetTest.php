<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\PhpStan;

use AdamczykPiotr\UltimateFormatter\Tests\TestCase;
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
            'app/Dto/Tag/Input/TagRename.php:7 phpat.testInputDtosAreNamedInput',
            'app/Dto/Tag/Input/TagRename.php:7 phpat.testInputDtosExtendBaseInput',
            'app/Dto/Tag/TagSummary.php:7 phpat.testOutputDtosAreNamedDto',
            'app/Exceptions/TagLocked.php:9 phpat.testExceptionsAreNamedException',
            'app/Exceptions/TagLocked.php:9 phpat.testExceptionsExtendBaseException',
            'app/Http/Controllers/TagController.php:13 ultimateFormatter.responseJson',
            'app/Http/Controllers/TagHandler.php:7 phpat.testControllersAreNamedController',
            'app/Http/Requests/TagStoreRequest.php:9 phpat.testNoClassExtendsFormRequest',
            'app/Http/Resources/TagPayload.php:9 phpat.testResourcesAreNamedResource',
            'app/Services/TagCleaner.php:7 phpat.testServicesAreNamedService',
            'app/Services/TagService.php:15 phpat.testServicesDoNotDependOnHttp',
            'app/Services/TagService.php:17 ultimateFormatter.httpInService',
            'app/Services/TagService.php:17 ultimateFormatter.sprintf',
            'app/Services/TagService.php:18 ultimateFormatter.arrayFunction',
            'app/Services/TagService.php:19 ultimateFormatter.fileExists',
            'app/Services/TagService.php:19 ultimateFormatter.methodExists',
            'app/Services/TagService.php:19 ultimateFormatter.pregFunction',
            'app/Services/TagService.php:20 ultimateFormatter.httpInService',
            'app/Services/TagService.php:21 ultimateFormatter.httpInService',
            'app/Services/TagService.php:22 larastan.noEnvCallsOutsideOfConfig',
            'app/Services/TagService.php:23 ultimateFormatter.httpInService',
            'app/Services/TagService.php:24 phpat.testServicesDoNotDependOnHttp',
            'app/Services/TagService.php:26 ultimateFormatter.modelAttributeArray',
            'app/Services/TagService.php:27 ultimateFormatter.modelAttributeArray',
            'app/Services/TagService.php:28 ultimateFormatter.modelAttributeArray',
            'app/Services/TagService.php:29 ultimateFormatter.modelAttributeArray',
            'app/Services/TagService.php:30 ultimateFormatter.modelAttributeArray',
            'app/Services/TagService.php:31 ultimateFormatter.modelAttributeArray',
            'app/Services/TagService.php:32 ultimateFormatter.modelRefresh',
            'app/Services/TagService.php:34 ultimateFormatter.appEnvironment',
            'database/migrations/2026_01_01_000000_create_tags_table.php:12 phpat.testMigrationsDoNotDependOnModels',
            'tests/TagServiceTest.php:15 ultimateFormatter.logShouldReceive',
            'tests/TagServiceTest.php:19 ultimateFormatter.appEnvironment',
        ], $this->analyseFixtures());
    }

    /**
     * @return array<int, string>
     */
    protected function analyseFixtures(): array
    {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/phpstan', 'analyse', '--configuration=' . self::FIXTURE_DIRECTORY . '/phpstan.neon', '--error-format=json', '--no-progress'],
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
