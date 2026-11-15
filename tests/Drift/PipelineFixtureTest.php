<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Drift;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

#[Group('drift')]
class PipelineFixtureTest extends DriftTestCase
{
    protected const BEFORE_DIRECTORY = __DIR__ . '/Fixture/before';
    protected const AFTER_DIRECTORY = __DIR__ . '/Fixture/after';

    /**
     * Only the planted sources are compared: install also writes its config files and composer scripts.
     */
    protected const COMPARED_DIRECTORIES = ['database', 'src'];

    /**
     * @return void
     */
    public function test_format_turns_the_planted_project_into_the_expected_one(): void
    {
        $filesystem = new Filesystem();
        $projectDirectory = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-pipeline-', true);
        $filesystem->mirror(self::BEFORE_DIRECTORY, $projectDirectory);
        $filesystem->symlink(realpath(self::ROOT_DIRECTORY . '/vendor') ?: self::ROOT_DIRECTORY . '/vendor', "{$projectDirectory}/vendor");

        try {
            $this->runFormatter('install', $projectDirectory);
            $this->runFormatter('format', $projectDirectory);
            $actual = $this->readSources($projectDirectory);
        } finally {
            $filesystem->remove($projectDirectory);
        }

        if ($this->updatesFixtures()) {
            $filesystem->remove(self::AFTER_DIRECTORY);
            foreach ($actual as $relativePath => $contents) {
                $filesystem->dumpFile(self::AFTER_DIRECTORY . "/{$relativePath}", $contents);
            }
        }

        $this->assertSame($this->readSources(self::AFTER_DIRECTORY), $actual);
    }

    /**
     * @param string $command
     * @param string $projectDirectory
     *
     * @return void
     */
    protected function runFormatter(string $command, string $projectDirectory): void
    {
        $process = new Process(
            [PHP_BINARY, self::ROOT_DIRECTORY . '/bin/ultimate-formatter', $command, "--path={$projectDirectory}", '--no-ansi'],
            $projectDirectory,
            timeout: null,
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), "ultimate-formatter {$command} failed:\n{$process->getOutput()}{$process->getErrorOutput()}");
    }

    /**
     * @param string $directory
     *
     * @return array<string, string>
     */
    protected function readSources(string $directory): array
    {
        $directories = collect(self::COMPARED_DIRECTORIES)
            ->map(fn(string $compared): string => "{$directory}/{$compared}")
            ->filter(fn(string $compared): bool => is_dir($compared));
        if ($directories->isEmpty()) {
            return [];
        }

        $finder = Finder::create()->files()->in($directories->all())->sortByName();

        return collect(iterator_to_array($finder, false))
            ->mapWithKeys(fn(SplFileInfo $file): array => [
                str_replace("{$directory}/", '', $file->getPathname()) => $file->getContents(),
            ])
            ->sortKeys()
            ->all();
    }
}
