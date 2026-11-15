<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\PhpCsFixer\IgnoreMarkers\ProtectedLineReverter;
use PHPUnit\Framework\TestCase;

class ProtectedLineReverterTest extends TestCase
{
    /**
     * @return void
     */
    public function test_keeps_a_protected_line_and_applies_the_change_on_its_neighbour(): void
    {
        $before = "<?php\n\$a = 4326;\n\$b = 22233;\n";
        $after = "<?php\n\$a = 4_326;\n\$b = 22_233;\n";

        $this->assertSame("<?php\n\$a = 4326;\n\$b = 22_233;\n", (new ProtectedLineReverter())->revert($before, $after, [2 => true]));
    }

    /**
     * @return void
     */
    public function test_an_added_line_next_to_a_protected_line_is_undone(): void
    {
        $before = "<?php\nfoo();\nreturn 1;\n";
        $after = "<?php\nfoo();\n\nreturn 1;\n";

        $this->assertSame($before, (new ProtectedLineReverter())->revert($before, $after, [3 => true]));
        $this->assertSame($after, (new ProtectedLineReverter())->revert($before, $after, [1 => true]));
    }

    /**
     * @return void
     */
    public function test_a_moved_line_undoes_the_rule_for_the_whole_file(): void
    {
        $before = "<?php\nuse B;\nuse A;\nuse C;\n\$a = 1;\n";
        $after = "<?php\nuse A;\nuse B;\nuse C;\n\$a = 1;\n";

        $this->assertSame($before, (new ProtectedLineReverter())->revert($before, $after, [2 => true]));
    }

    /**
     * @return void
     */
    public function test_a_half_change_that_does_not_parse_undoes_the_rule_for_the_whole_file(): void
    {
        $before = "<?php\nif (\$a):\n    foo();\nendif;\n";
        $after = "<?php\nif (\$a) {\n    foo();\n}\n";

        $this->assertSame($before, (new ProtectedLineReverter())->revert($before, $after, [2 => true]));
    }

    /**
     * @return void
     */
    public function test_leaves_the_change_when_no_line_is_protected(): void
    {
        $before = "<?php\n\$a = array();\n";
        $after = "<?php\n\$a = [];\n";

        $this->assertSame($after, (new ProtectedLineReverter())->revert($before, $after, []));
        $this->assertSame($after, (new ProtectedLineReverter())->revert($before, $after, [5 => true]));
    }
}
