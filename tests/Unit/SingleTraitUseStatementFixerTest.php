<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Tests\Unit;

use AdamczykPiotr\UltimateFormatter\PhpCsFixer\Fixers\SingleTraitUseStatementFixer;
use Iterator;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class SingleTraitUseStatementFixerTest extends TestCase
{
    /**
     * @param string $input
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('provideCases')]
    public function test_merges_trait_statements(string $input, string $expected): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        (new SingleTraitUseStatementFixer())->fix(new SplFileInfo(__FILE__), $tokens);

        $this->assertSame($expected, $tokens->generateCode());
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function provideCases(): Iterator
    {
        yield 'merges separate statements' => [
            "<?php\nclass Tag\n{\n    use SoftDeletes;\n    use HasFactory;\n\n    public int \$id = 1;\n}\n",
            "<?php\nclass Tag\n{\n    use SoftDeletes, HasFactory;\n\n    public int \$id = 1;\n}\n",
        ];

        yield 'merges a statement that already holds several traits' => [
            "<?php\nclass Tag\n{\n    use SoftDeletes,  Notifiable;\n    use \\App\\Concerns\\HasColor;\n}\n",
            "<?php\nclass Tag\n{\n    use SoftDeletes, Notifiable, \\App\\Concerns\\HasColor;\n}\n",
        ];

        yield 'merges statements spread through the class' => [
            "<?php\nclass Tag\n{\n    use SoftDeletes;\n    public const TYPE = 'tag';\n    use HasFactory;\n}\n",
            "<?php\nclass Tag\n{\n    use SoftDeletes, HasFactory;\n    public const TYPE = 'tag';\n}\n",
        ];

        yield 'merges each class on its own' => [
            "<?php\nclass Tag\n{\n    use A;\n    use B;\n}\nclass Post\n{\n    use C;\n    use D;\n}\n",
            "<?php\nclass Tag\n{\n    use A, B;\n}\nclass Post\n{\n    use C, D;\n}\n",
        ];

        yield 'keeps a single statement' => [
            "<?php\nclass Tag\n{\n    use HasFactory;\n}\n",
            "<?php\nclass Tag\n{\n    use HasFactory;\n}\n",
        ];

        yield 'skips a conflict resolution block' => [
            "<?php\nclass Tag\n{\n    use A { a as b; }\n    use B;\n}\n",
            "<?php\nclass Tag\n{\n    use A { a as b; }\n    use B;\n}\n",
        ];

        yield 'skips statements with comments' => [
            "<?php\nclass Tag\n{\n    use A; \n    use /* needed for x */ B;\n}\n",
            "<?php\nclass Tag\n{\n    use A; \n    use /* needed for x */ B;\n}\n",
        ];

        yield 'moves a docblock of a later statement above the merged one' => [
            "<?php\nclass Field\n{\n    use EmitsLegacyWireKeys;\n    use DateSerializationTrait;\n\n    /** @use HasFactory<FieldFactory> */\n    use HasFactory;\n\n    use RevisionedRow;\n}\n",
            "<?php\nclass Field\n{\n    /** @use HasFactory<FieldFactory> */\n    use EmitsLegacyWireKeys, DateSerializationTrait, HasFactory, RevisionedRow;\n}\n",
        ];

        yield 'keeps the docblock of the first statement' => [
            "<?php\nclass Field\n{\n    /** @use HasFactory<FieldFactory> */\n    use HasFactory;\n    use RevisionedRow;\n}\n",
            "<?php\nclass Field\n{\n    /** @use HasFactory<FieldFactory> */\n    use HasFactory, RevisionedRow;\n}\n",
        ];

        yield 'combines docblocks of several statements' => [
            "<?php\nclass Field\n{\n    /** @use HasFactory<FieldFactory> */\n    use HasFactory;\n    /** @use HasTags<Tag> */\n    use HasTags;\n}\n",
            "<?php\nclass Field\n{\n    /**\n     * @use HasFactory<FieldFactory>\n     * @use HasTags<Tag>\n     */\n    use HasFactory, HasTags;\n}\n",
        ];

        yield 'skips a plain comment above a statement' => [
            "<?php\nclass Tag\n{\n    use A;\n    // needed for x\n    use B;\n}\n",
            "<?php\nclass Tag\n{\n    use A;\n    // needed for x\n    use B;\n}\n",
        ];

        yield 'leaves namespace imports alone' => [
            "<?php\nuse App\\A;\nuse App\\B;\nclass Tag\n{\n    use A;\n    use B;\n}\n",
            "<?php\nuse App\\A;\nuse App\\B;\nclass Tag\n{\n    use A, B;\n}\n",
        ];

        yield 'merges a nested anonymous class and its outer class' => [
            "<?php\nclass Outer\n{\n    use A;\n\n    public function make(): object\n    {\n        return new class {\n            use X;\n            use Y;\n        };\n    }\n\n    use B;\n}\n",
            "<?php\nclass Outer\n{\n    use A, B;\n\n    public function make(): object\n    {\n        return new class {\n            use X, Y;\n        };\n    }\n}\n",
        ];
    }
}
