<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        '@PSR12' => true,
        'strict_param' => true,
        'psr_autoloading' => true,
        'align_multiline_comment' => ['comment_type' => 'phpdocs_only'], // psr-5
        'phpdoc_to_comment' => false,
        'no_superfluous_phpdoc_tags' => false,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'cast_spaces' => ['space' => 'none'],
        'concat_space' => ['spacing' => 'one'],
        'compact_nullable_type_declaration' => true,
        'nullable_type_declaration' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'declare_equal_normalize' => ['space' => 'single'],
        'declare_strict_types' => true,
        'increment_style' => ['style' => 'post'],
        'list_syntax' => ['syntax' => 'short'],
        'final_class' => true,
        'echo_tag_syntax' => ['format' => 'long'],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
        'phpdoc_align' => false,
        'phpdoc_no_empty_return' => false,
        'phpdoc_order' => true, // psr-5
        'phpdoc_no_useless_inheritdoc' => false,
        'protected_to_private' => false,
        'yoda_style' => false,
        'strict_comparison' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'const', 'function']
        ],
        'single_line_throw' => false,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => false,])
    ->setFinder($finder);
