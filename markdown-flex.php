#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kijtkd\MarkdownFlexConverter;
use Kijtkd\Theme\DarkTheme;

function showHelp() {
    echo "Usage: php markdown-flex.php [OPTIONS]\n";
    echo "Convert Markdown to LINE WORKS Bot Flexible Template\n\n";
    echo "Options:\n";
    echo "  -f <file>     Input Markdown file (required)\n";
    echo "  -o <file>     Output JSON file (optional, outputs to stdout if not specified)\n";
    echo "  -t <theme>    Theme to use (default|dark)\n";
    echo "  -c            Convert code blocks to images\n";
    echo "  -h            Show this help message\n\n";
    echo "Examples:\n";
    echo "  php markdown-flex.php -f input.md\n";
    echo "  php markdown-flex.php -f input.md -o output.json\n";
    echo "  php markdown-flex.php -f input.md -t dark\n";
    exit(0);
}

// Parse command line arguments using getopt
$options = getopt("f:o:t:ch", [], $optind);

if (isset($options['h'])) {
    showHelp();
}

$args = [
    'file' => $options['f'] ?? null,
    'output' => $options['o'] ?? null,
    'theme' => $options['t'] ?? null,
    'code_img' => isset($options['c'])
];

// Check required arguments
if (!isset($args['file'])) {
    echo "Error: Input file (-f) is required\n\n";
    showHelp();
}

// Check if input file exists
if (!file_exists($args['file'])) {
    echo "Error: Input file '{$args['file']}' not found\n";
    exit(1);
}

// Read markdown content
$markdown = file_get_contents($args['file']);
if ($markdown === false) {
    echo "Error: Could not read input file '{$args['file']}'\n";
    exit(1);
}

// Create converter
$converter = new MarkdownFlexConverter();

// Set theme
if (isset($args['theme'])) {
    switch (strtolower($args['theme'])) {
        case 'dark':
            $converter->setTheme(new DarkTheme());
            break;
        case 'default':
        default:
            // Already using default theme
            break;
    }
}

// Set options
$options = [];
if (isset($args['code_img']) && $args['code_img']) {
    $options['code_img'] = true;
}
if (!empty($options)) {
    $converter->setOptions($options);
}

// Convert markdown
try {
    [$json, $altText] = $converter->convert($markdown);
    
    // Output just the bubble/carousel content for simulator
    $jsonOutput = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Output result
    if (isset($args['output'])) {
        // Write to file
        if (file_put_contents($args['output'], $jsonOutput) === false) {
            echo "Error: Could not write to output file '{$args['output']}'\n";
            exit(1);
        }
        echo "Output written to: {$args['output']}\n";
    } else {
        // Output to stdout
        echo $jsonOutput . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}