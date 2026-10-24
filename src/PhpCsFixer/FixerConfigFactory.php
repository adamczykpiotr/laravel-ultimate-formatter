<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\PhpCsFixer;

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

class FixerConfigFactory
{
    /**
     * @param Finder $finder
     * @param non-empty-string $cacheFile
     * @return Config
     */
    public static function create(Finder $finder, string $cacheFile): Config
    {
        $parallelConfig = ParallelConfigFactory::detect();

        return (new Config())
            ->setRiskyAllowed(true)
            ->setParallelConfig($parallelConfig)
            ->setRules(self::rules())
            ->setFinder($finder)
            ->setCacheFile($cacheFile);
    }

    /**
     * @return array<string, array<string, mixed>|bool>
     */
    public static function rules(): array
    {
        // `no_superfluous_phpdoc_tags`, `phpdoc_no_empty_return` and `phpdoc_summary` stay off:
        // every method keeps @param/@return even when native types cover them.
        return [
            'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
            'phpdoc_align' => ['align' => 'left'],
            'phpdoc_indent' => true,
            'phpdoc_trim' => true,
            'phpdoc_trim_consecutive_blank_line_separation' => true,
            'phpdoc_separation' => [
                'groups' => [
                    ['param', 'return', 'throws'],
                    ['deprecated', 'link', 'see', 'since'],
                    ['author', 'copyright', 'license'],
                    ['category', 'package', 'subpackage'],
                    ['property', 'property-read', 'property-write'],
                ],
            ],
            'phpdoc_single_line_var_spacing' => true,
            'phpdoc_order' => ['order' => ['param', 'return', 'throws']],
            'phpdoc_line_span' => ['property' => 'multi', 'method' => 'multi', 'const' => 'multi'],
            'no_empty_phpdoc' => true,
            'no_blank_lines_after_phpdoc' => true,
            'phpdoc_no_access' => true,
            'phpdoc_no_package' => true,
            'phpdoc_no_useless_inheritdoc' => true,
            'phpdoc_return_self_reference' => true,
            'phpdoc_var_without_name' => true,
            'phpdoc_var_annotation_correct_order' => true,
            'phpdoc_scalar' => true,
            'phpdoc_types' => ['groups' => ['simple', 'alias', 'meta']],
            'phpdoc_types_order' => ['sort_algorithm' => 'none', 'null_adjustment' => 'always_last'],
            'phpdoc_list_type' => true,
            'fully_qualified_strict_types' => [
                'import_symbols' => true,
                'phpdoc_tags' => ['param', 'return', 'throws', 'var', 'type'],
            ],
            'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],

            'no_unused_imports' => true,
            'ordered_imports' => [
                'sort_algorithm' => 'alpha',
                'imports_order' => ['class', 'function', 'const'],
            ],
            'single_import_per_statement' => true,
            'no_leading_import_slash' => true,
            'single_line_after_imports' => true,

            'constant_case' => ['case' => 'lower'],
            'lowercase_keywords' => true,
            'lowercase_static_reference' => true,
            'magic_constant_casing' => true,
            'native_function_casing' => true,
            'native_type_declaration_casing' => true,

            'array_syntax' => ['syntax' => 'short'],
            'trailing_comma_in_multiline' => ['elements' => ['arrays']],
            'no_trailing_comma_in_singleline' => true,
            'whitespace_after_comma_in_array' => true,

            'binary_operator_spaces' => ['default' => 'single_space'],
            'concat_space' => ['spacing' => 'one'],
            'object_operator_without_whitespace' => true,
            'types_spaces' => ['space' => 'none'],
            'cast_spaces' => ['space' => 'none'],
            'unary_operator_spaces' => true,

            'no_trailing_whitespace' => true,
            'no_trailing_whitespace_in_comment' => true,
            'no_whitespace_in_blank_line' => true,
            'indentation_type' => true,
            'line_ending' => true,
            'single_blank_line_at_eof' => true,

            'braces_position' => [
                'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
                'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
                'control_structures_opening_brace' => 'same_line',
                'anonymous_functions_opening_brace' => 'same_line',
                'anonymous_classes_opening_brace' => 'same_line',
                'allow_single_line_empty_anonymous_classes' => false,
                'allow_single_line_anonymous_functions' => false,
            ],
            'control_structure_continuation_position' => ['position' => 'same_line'],
            'no_alternative_syntax' => true,
            'switch_case_semicolon_to_colon' => true,
            'no_break_comment' => true,
            'single_line_empty_body' => true,

            'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
            'no_spaces_after_function_name' => true,
            'type_declaration_spaces' => true,
            'return_type_declaration' => ['space_before' => 'none'],
            'modifier_keywords' => ['elements' => ['const', 'method', 'property']],

            'class_attributes_separation' => [
                'elements' => [
                    'const' => 'none',
                    'method' => 'one',
                    'property' => 'none',
                    'trait_import' => 'none',
                ],
            ],
            'no_blank_lines_after_class_opening' => true,
            'blank_line_after_opening_tag' => true,
            'blank_line_after_namespace' => true,
            'blank_lines_before_namespace' => true,
            'no_extra_blank_lines' => [
                'tokens' => [
                    'extra',
                    'throw',
                    'use',
                    'curly_brace_block',
                    'parenthesis_brace_block',
                    'square_brace_block',
                ],
            ],

            'single_line_comment_style' => ['comment_types' => ['hash']],
            'declare_strict_types' => true,
        ];
    }
}
