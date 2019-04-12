<?php

$finder = PhpCsFixer\Finder::create()
    ->notPath('core/crud2.sample.php')
    ->notPath('core/crud.sample.php')
    ->exclude('vendor')
    ->exclude('config')
    ->exclude('apps/admin/views/')
    ->exclude('apps/default/views/')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony'                    => true,
        '@PhpCsFixer'                 => true,
        '@DoctrineAnnotation'         => true,
        '@PHP71Migration'             => true,
        'array_syntax'                => ['syntax' => 'short'],
        'single_import_per_statement' => false,
        'binary_operator_spaces'      => ['default' => 'align_single_space_minimal'],
        'concat_space'                => ['spacing' => 'one'],
        'combine_nested_dirname'      => true, //risky
        'dir_constant'                => true, //risky
        'fopen_flag_order'            => true, //risky
        'fopen_flags'                 => true, //risky
        'function_to_constant'        => true, //risky
        'implode_call'                => true, //risky
        'is_null'                     => true, //risky
        'mb_str_functions'            => true, //risky
        'modernize_types_casting'     => true, //risky
        'phpdoc_types_order'          => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'random_api_migration'        => true, //risky
    ])
    ->setFinder($finder)
;
