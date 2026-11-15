<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class FixerIgnoreMarkersTest extends TestCase
{
    protected const ROOT_DIRECTORY = __DIR__ . '/../..';

    protected Filesystem $filesystem;
    protected string $projectPath;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->projectPath = sys_get_temp_dir() . '/' . uniqid('ultimate-formatter-ignore-', true);
        $autoload = realpath(self::ROOT_DIRECTORY . '/vendor/autoload.php');
        $this->filesystem->dumpFile("{$this->projectPath}/.php-cs-fixer.php", implode("\n", [
            '<?php',
            "require '{$autoload}';",
            '$finder = PhpCsFixer\Finder::create()->in(__DIR__ . \'/src\');',
            'return AdamczykPiotr\UltimateFormatter\PhpCsFixer\FixerConfigFactory::create($finder, __DIR__ . \'/.php-cs-fixer.cache\');',
            '',
        ]));
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectPath);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_a_marker_keeps_its_line_and_the_rest_of_the_file_is_fixed(): void
    {
        $this->filesystem->dumpFile("{$this->projectPath}/src/Codes.php", implode("\n", [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '$postal = 11122; // formatter-ignore UltimateFormatter/numeric_literal_separator',
            '$other = 22233;',
            '// formatter-ignore UltimateFormatter/numeric_literal_separator, array_syntax -- EPSG code',
            '$srid = array(4326, 10000);',
            '$next = array(4326, 10000);',
            '',
        ]));

        $process = $this->fix();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame(implode("\n", [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '$postal = 11122; // formatter-ignore UltimateFormatter/numeric_literal_separator',
            '$other = 22_233;',
            '// formatter-ignore UltimateFormatter/numeric_literal_separator, array_syntax -- EPSG code',
            '$srid = array(4326, 10000);',
            '$next = [4_326, 10_000];',
            '',
        ]), $this->filesystem->readFile("{$this->projectPath}/src/Codes.php"));
    }

    /**
     * @return void
     */
    public function test_a_marker_naming_an_unknown_rule_fails_the_file(): void
    {
        $code = "<?php\n\ndeclare(strict_types=1);\n\n\$srid = array(4326); // formatter-ignore numeric_literal_seperator\n";
        $this->filesystem->dumpFile("{$this->projectPath}/src/Codes.php", $code);

        $process = $this->fix('--sequential', '-vvv');

        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('names rules that are not enabled: numeric_literal_seperator.', $process->getOutput() . $process->getErrorOutput());
        $this->assertSame($code, $this->filesystem->readFile("{$this->projectPath}/src/Codes.php"));
    }

    /**
     * @param string ...$options
     *
     * @return Process
     */
    protected function fix(string ...$options): Process
    {
        $process = new Process(
            [PHP_BINARY, self::ROOT_DIRECTORY . '/vendor/bin/php-cs-fixer', 'fix', "--config={$this->projectPath}/.php-cs-fixer.php", '--show-progress=none', ...$options],
            $this->projectPath,
            ['PHP_CS_FIXER_IGNORE_ENV' => '1'],
            timeout: null,
        );
        $process->run();

        return $process;
    }
}
