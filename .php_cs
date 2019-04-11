<?php

$finder = PhpCsFixer\Finder::create()
    ->notPath('core/crud2.sample.php')
    ->notPath('core/crud.sample.php')
    ->exclude('vendor')
    ->exclude('apps/admin/views/')
    ->exclude('apps/default/views/')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony'                    => true,
        '@PhpCsFixer'                 => true,
        '@DoctrineAnnotation'         => true,
        'array_syntax'                => ['syntax' => 'short'],
        'single_import_per_statement' => false,
        'binary_operator_spaces'      => [
            'align_double_arrow' => true,
            'default'            => ['align_single_space_minimal'],
        ],
        'concat_space'                => ['spacing' => 'one'],
        'combine_nested_dirname'      => true,
        // Following rules are risky but safe for us, since we don't override any php native function.
        'dir_constant'                => true,
        'fopen_flag_order'            => true,
        'fopen_flags'                 => true,
        'function_to_constant'        => true,
        'implode_call'                => true,
        'is_null'                     => true,
        'mb_str_functions'            => true,
        'modernize_types_casting'     => true,
    ])
    ->setFinder($finder)
;
