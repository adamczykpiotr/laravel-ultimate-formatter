<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Drift;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

/**
 * Formats a planted project with the real pipeline: an upstream rule that changes or disappears shows up as a diff.
 * `composer fixtures:update` rewrites Fixture/after instead of comparing against it.
 */
#[Group('drift')]
class PipelineFixtureTest extends TestCase
{
    protected const ROOT_DIRECTORY = __DIR__ . '/../..';
    protected const BEFORE_DIRECTORY = __DIR__ . '/Fixture/before';
    protected const AFTER_DIRECTORY = __DIR__ . '/Fixture/after';

    /**
     * @return void
     */
    public function test_format_turns_the_planted_project_into_the_expected_one(): void
    {
        $filesystem = new Filesystem();
        $projectDirectory = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-pipeline-', true);
        $filesystem->mirror(self::BEFORE_DIRECTORY, $projectDirectory);
        $filesystem->symlink(self::ROOT_DIRECTORY . '/vendor', "{$projectDirectory}/vendor");

        try {
            $this->runFormatter('install', $projectDirectory);
            $this->runFormatter('format', $projectDirectory);
            $actual = $this->readSources($projectDirectory);
        } finally {
            $filesystem->remove($projectDirectory);
        }

        if (getenv('UPDATE_FIXTURES') === '1') {
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
        $process = new Process([PHP_BINARY, self::ROOT_DIRECTORY . '/bin/ultimate-formatter', $command, "--path={$projectDirectory}"], $projectDirectory, timeout: null);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), "ultimate-formatter {$command} failed:\n{$process->getOutput()}{$process->getErrorOutput()}");
    }

    /**
     * Only src and database are compared: install also writes its config files into the project.
     *
     * @param string $directory
     *
     * @return array<string, string>
     */
    protected function readSources(string $directory): array
    {
        $finder = Finder::create()->files()->in(["{$directory}/src", "{$directory}/database"])->sortByName();

        return collect(iterator_to_array($finder, false))
            ->mapWithKeys(fn(SplFileInfo $file): array => [str_replace("{$directory}/", '', $file->getPathname()) => $file->getContents()])
            ->sortKeys()
            ->all();
    }
}
