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

    /**
     * @param string $code
     *
     * @return void
     */
    #[DataProvider('provideCodes')]
    public function test_leaves_ids_and_years_alone(string $code): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode("<?php\n{$code}\n");

        (new NumericLiteralSeparatorFixer())->fix(new SplFileInfo(__FILE__), $tokens);

        $this->assertSame("<?php\n{$code}\n", $tokens->generateCode());
    }

    /**
     * @return Iterator<string, array{string}>
     */
    public static function provideCodes(): Iterator
    {
        yield 'variable ending with id' => ['$userId = 10001;'];
        yield 'bare id' => ['$id = 10001;'];
        yield 'list of ids' => ['new Job(precinctIds: [5757, 10001]);'];
        yield 'nested list of ids' => ['$ids = [[10001, 10002], [10003]];'];
        yield 'acronym id' => ["\$row = ['EdielID' => 11111];"];
        yield 'assignment to an id key' => ["\$json['form']['current_revision_id'] = 999999;"];
        yield 'snake case id' => ["\$row = ['cbdg_id' => 10001];"];
        yield 'named argument with year' => ['new Borehole(drillingYear: 1990);'];
        yield 'year in an expression' => ['new Borehole(documentationYear: 2000 + $index);'];
        yield 'constant with year' => ['class Census { public const int FIRST_YEAR = 1921; }'];
        yield 'class constant key with id' => ['$options = [Wfs::FEATURE_ID => 10001];'];
        yield 'parameter default with year' => ['function report(int $year = 2026) {}'];
        yield 'property ending with id' => ['$this->parentId = 10001;'];
        yield 'case of a year enum' => ['enum CensusYear: int { case FIRST = 1921; }'];
    }

    /**
     * @param string $input
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('provideQuantities')]
    public function test_still_groups_quantities_next_to_codes(string $input, string $expected): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode("<?php\n{$input}\n");

        (new NumericLiteralSeparatorFixer())->fix(new SplFileInfo(__FILE__), $tokens);

        $this->assertSame("<?php\n{$expected}\n", $tokens->generateCode());
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function provideQuantities(): Iterator
    {
        yield 'name merely containing id' => ['$validUntil = 3600;', '$validUntil = 3_600;'];
        yield 'acronym id followed by a word' => ['$userIDCount = 1000;', '$userIDCount = 1_000;'];
        yield 'assignment to a quantity key' => ["\$limits['max_size'] = 1000;", "\$limits['max_size'] = 1_000;"];
        yield 'grid size' => ['$grid = 1000;', '$grid = 1_000;'];
        yield 'srid' => ['$srid = 4326;', '$srid = 4_326;'];
        yield 'positional argument' => ['ST::transform($point, 4326);', 'ST::transform($point, 4_326);'];
        yield 'list passed positionally' => ['foo([1000, 2000]);', 'foo([1_000, 2_000]);'];
        yield 'call inside an id argument' => ['new Job(taskId: max(1000, $x));', 'new Job(taskId: max(1_000, $x));'];
        yield 'enum unrelated to codes' => ['enum Limit: int { case MAX = 10000; }', 'enum Limit: int { case MAX = 10_000; }'];
    }
}
