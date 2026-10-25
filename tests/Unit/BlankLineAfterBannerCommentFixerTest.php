<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers\BlankLineAfterBannerCommentFixer;
use Iterator;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class BlankLineAfterBannerCommentFixerTest extends TestCase
{
    /**
     * @param string $input
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('provideCases')]
    public function test_fixes_banner_spacing(string $input, string $expected): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        (new BlankLineAfterBannerCommentFixer())->fix(new SplFileInfo(__FILE__), $tokens);

        $this->assertSame($expected, $tokens->generateCode());
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function provideCases(): Iterator
    {
        $banner = "    /*\n    |----------\n    | Accessors\n    |----------\n    */\n";
        $method = "    public function name(): string\n    {\n        return 'name';\n    }\n";

        yield 'adds blank line before a method' => [
            "<?php\nclass Foo\n{\n{$banner}{$method}}\n",
            "<?php\nclass Foo\n{\n{$banner}\n{$method}}\n",
        ];

        yield 'adds blank line before a docblock' => [
            "<?php\nclass Foo\n{\n{$banner}    /**\n     * Name.\n     */\n{$method}}\n",
            "<?php\nclass Foo\n{\n{$banner}\n    /**\n     * Name.\n     */\n{$method}}\n",
        ];

        yield 'keeps an existing blank line' => [
            "<?php\nclass Foo\n{\n{$banner}\n{$method}}\n",
            "<?php\nclass Foo\n{\n{$banner}\n{$method}}\n",
        ];

        yield 'skips a banner closing the class' => [
            "<?php\nclass Foo\n{\n{$method}\n{$banner}}\n",
            "<?php\nclass Foo\n{\n{$method}\n{$banner}}\n",
        ];

        yield 'skips plain block comments' => [
            "<?php\nclass Foo\n{\n    /*\n     * Not a banner.\n     */\n{$method}}\n",
            "<?php\nclass Foo\n{\n    /*\n     * Not a banner.\n     */\n{$method}}\n",
        ];

        yield 'skips docblocks' => [
            "<?php\nclass Foo\n{\n    /**\n    | Docblock.\n    */\n{$method}}\n",
            "<?php\nclass Foo\n{\n    /**\n    | Docblock.\n    */\n{$method}}\n",
        ];
    }
}
