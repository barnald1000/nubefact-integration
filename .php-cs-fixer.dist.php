<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setRules([
        '@PSR12'                                   => true,

        // Blank lines
        'no_blank_lines_after_class_opening'       => true,
        'blank_line_after_opening_tag'             => false,
        'blank_line_before_statement'              => [
            'statements' => ['declare', 'return'],
        ],
        'class_attributes_separation'              => [
            'elements' => [
                'method' => 'one',
            ],
        ],

        // Spacing
        'single_line_empty_body'                   => true,
        'single_blank_line_at_eof'                 => true,
        'cast_spaces'                              => ['space' => 'single'],
        'type_declaration_spaces'                  => true,
        'array_indentation'                        => true,
        'trim_array_spaces'                        => true,
        'whitespace_after_comma_in_array'          => true,
        'single_space_around_construct'            => true,
        'method_chaining_indentation'              => false,
        'method_argument_space'                    => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'no_spaces_after_function_name'            => true,
        'function_declaration'                     => [
            'closure_fn_spacing' => 'none',
        ],
        'braces_position'                          => [
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'classes_opening_brace'   => 'next_line_unless_newline_at_signature_end',
        ],

        // Casing
        'class_reference_name_casing'              => true,
        'lowercase_static_reference'               => true,
        'magic_method_casing'                      => true,
        'native_function_casing'                   => true,
        'native_type_declaration_casing'           => true,

        // Language and design safeguards
        'protected_to_private'                     => true,
        'modifier_keywords'                        => ['elements' => ['const', 'property', 'method']],
        'no_useless_else'                          => true,
        'declare_strict_types'                     => true,

        // Operators
        'assign_null_coalescing_to_coalesce_equal' => true,
        'no_useless_concat_operator'               => true,
        'object_operator_without_whitespace'       => true,
        'ternary_to_elvis_operator'                => true,
        'ternary_to_null_coalescing'               => true,

        // PHPUnit
        'php_unit_construct'                       => true,
        'php_unit_dedicate_assert'                 => true,
        'php_unit_dedicate_assert_internal_type'   => true,
        'php_unit_expectation'                     => true,

        // Imports and alignment
        'no_unused_imports'                        => true,
        'ordered_imports'                          => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => ['class', 'function', 'const'],
        ],
        'binary_operator_spaces'                   => [
            'operators' => [
                '??=' => 'align_single_space',
                '='   => 'align_single_space',
                '=>'  => 'align_single_space_by_scope',
            ],
        ],
    ]);
