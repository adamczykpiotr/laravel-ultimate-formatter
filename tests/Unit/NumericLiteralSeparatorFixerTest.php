<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers\NumericLiteralSeparatorFixer;
use Iterator;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class NumericLiteralSeparatorFixerTest extends TestCase
{
    /**
     * @param string $input
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('provideCases')]
    public function test_groups_decimal_literals_by_thousands(string $input, string $expected): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode("<?php\n\$value = {$input};\n");

        (new NumericLiteralSeparatorFixer())->fix(new SplFileInfo(__FILE__), $tokens);

        $this->assertSame("<?php\n\$value = {$expected};\n", $tokens->generateCode());
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function provideCases(): Iterator
    {
        yield 'thousand' => ['1000', '1_000'];
        yield 'ten thousand' => ['10000', '10_000'];
        yield 'million' => ['1000000', '1_000_000'];
        yield 'large integer' => ['1234567890', '1_234_567_890'];
        yield 'float keeps its fraction' => ['1234567.891234', '1_234_567.891234'];
        yield 'misplaced separator' => ['1000_000', '1_000_000'];
        yield 'separator below a thousand' => ['10_0', '100'];
        yield 'already grouped' => ['1_000_000', '1_000_000'];
        yield 'below a thousand' => ['999', '999'];
        yield 'zero' => ['0', '0'];
        yield 'small float' => ['0.12345', '0.12345'];
        yield 'hex' => ['0x1234ABCD', '0x1234ABCD'];
        yield 'binary' => ['0b1010101010', '0b1010101010'];
        yield 'octal' => ['0o1234567', '0o1234567'];
        yield 'legacy octal' => ['012345', '012345'];
        yield 'exponent' => ['12345e3', '12345e3'];
    }
}
