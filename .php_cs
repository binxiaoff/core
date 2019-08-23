<?php

$finder = PhpCsFixer\Finder::create()
    ->in('apps')->exclude(['admin/views/'])
    ->in(['bin', 'public', 'src', 'tests'])
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony'                               => true,
        '@PhpCsFixer'                            => true,
        '@DoctrineAnnotation'                    => true,
        '@PHP71Migration'                        => true,
        '@PHPUnit60Migration:risky'              => true,
        'array_syntax'                           => ['syntax' => 'short'],
        'single_import_per_statement'            => false,
        'binary_operator_spaces'                 => ['default' => 'align_single_space_minimal'],
        'concat_space'                           => ['spacing' => 'one'],
        'combine_nested_dirname'                 => true, //risky
        'dir_constant'                           => true, //risky
        'fopen_flag_order'                       => true, //risky
        'fopen_flags'                            => true, //risky
        'function_to_constant'                   => true, //risky
        'implode_call'                           => true, //risky
        'is_null'                                => true, //risky
        'mb_str_functions'                       => true, //risky
        'modernize_types_casting'                => true, //risky
        'phpdoc_types_order'                     => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'php_unit_construct'                     => true, //risky
        'php_unit_set_up_tear_down_visibility'   => true, //risky
        'php_unit_strict'                        => true, //risky
        'php_unit_test_case_static_method_calls' => true, //risky
        'random_api_migration'                   => true, //risky
        'declare_strict_types'                   => true, //risky
    ])
    ->setFinder($finder)
    ;
