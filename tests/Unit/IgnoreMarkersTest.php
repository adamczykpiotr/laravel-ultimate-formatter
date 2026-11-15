<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\Ignore\IgnoreMarkers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IgnoreMarkersTest extends TestCase
{
    /**
     * @return void
     */
    public function test_a_marker_at_the_end_of_a_line_ignores_that_line(): void
    {
        $markers = IgnoreMarkers::fromCode("<?php\n\$a = 4326; // formatter-ignore numeric_literal_separator\n\$b = 4326;\n");

        $this->assertSame([2 => ['numeric_literal_separator']], $markers->targets);
        $this->assertSame([], $markers->markerLines);
        $this->assertTrue($markers->ignores(2, 'numeric_literal_separator'));
        $this->assertFalse($markers->ignores(3, 'numeric_literal_separator'));
    }

    /**
     * @return void
     */
    public function test_a_marker_alone_on_its_line_ignores_the_next_line_of_code(): void
    {
        $markers = IgnoreMarkers::fromCode(implode("\n", [
            '<?php',
            '# formatter-ignore array_syntax',
            '',
            '// formatter-ignore ChangeSwitchToMatchRector, ultimateFormatter.disallowSprintf -- kept for the legacy API',
            '/** @var int $a */',
            '$a = array(sprintf("%d", 1));',
            '',
        ]));

        $this->assertSame([6 => ['array_syntax', 'ChangeSwitchToMatchRector', 'ultimateFormatter.disallowSprintf']], $markers->targets);
        $this->assertSame([2 => ['array_syntax'], 4 => ['ChangeSwitchToMatchRector', 'ultimateFormatter.disallowSprintf']], $markers->markerLines);
        $this->assertSame([6 => true, 2 => true], $markers->protectedLines('array_syntax'));
        $this->assertSame(['ChangeSwitchToMatchRector', 'array_syntax', 'ultimateFormatter.disallowSprintf'], $markers->identifiers());
    }

    /**
     * @return void
     */
    public function test_a_multi_line_token_moves_the_line_count_on(): void
    {
        $markers = IgnoreMarkers::fromCode("<?php\n\$a = 'one\ntwo'; // formatter-ignore single_quote\n");

        $this->assertSame([3 => ['single_quote']], $markers->targets);
    }

    /**
     * @param string $code
     *
     * @return void
     */
    #[DataProvider('provideNonMarkers')]
    public function test_ignores_text_that_is_no_marker(string $code): void
    {
        $this->assertTrue(IgnoreMarkers::fromCode($code)->isEmpty());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function provideNonMarkers(): array
    {
        return [
            'longer word' => ["<?php\n\$a = 1; // formatter-ignored array_syntax\n"],
            'string' => ["<?php\n\$a = '// formatter-ignore array_syntax';\n"],
            'docblock' => ["<?php\n/** formatter-ignore array_syntax */\n\$a = 1;\n"],
            'block comment' => ["<?php\n/* formatter-ignore array_syntax */\n\$a = 1;\n"],
            'prose' => ["<?php\n// Use formatter-ignore array_syntax here.\n\$a = 1;\n"],
        ];
    }

    /**
     * @param string $code
     *
     * @return void
     */
    #[DataProvider('provideMalformedMarkers')]
    public function test_reports_a_marker_without_a_valid_identifier_list(string $code): void
    {
        $markers = IgnoreMarkers::fromCode($code);

        $this->assertSame([2], array_keys($markers->errors));
        $this->assertSame([], $markers->targets);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function provideMalformedMarkers(): array
    {
        return [
            'no identifier' => ["<?php\n\$a = 1; // formatter-ignore\n"],
            'empty identifier' => ["<?php\n\$a = 1; // formatter-ignore array_syntax,\n"],
            'space separated' => ["<?php\n\$a = 1; // formatter-ignore array_syntax single_quote\n"],
        ];
    }

    /**
     * @return void
     */
    public function test_tells_the_tools_identifiers_apart(): void
    {
        $this->assertTrue(IgnoreMarkers::isFixerIdentifier('array_syntax'));
        $this->assertTrue(IgnoreMarkers::isFixerIdentifier('UltimateFormatter/numeric_literal_separator'));
        $this->assertTrue(IgnoreMarkers::isRectorIdentifier('ChangeSwitchToMatchRector'));
        $this->assertTrue(IgnoreMarkers::isPhpStanIdentifier('ultimateFormatter.disallowSprintf'));
        $this->assertFalse(IgnoreMarkers::isRectorIdentifier('phpat.testServicesAreNamedRector'));
    }
}
