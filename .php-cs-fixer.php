<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('tests')
    ->exclude('vendor')
    ->exclude('storage')
    ->name('*.php');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_definition' => ['multi_line_extended_class_definition' => true],
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'method_chaining_indentation' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'return_type_declaration' => ['space_before' => 'single'],
        'trailing_comma_in_multiline' => true,
        'yoda_style' => false,
    ])
    ->setFinder($finder);
