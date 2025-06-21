#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use MdFlex\FlexValidator;

function showHelp() {
    echo "Usage: php flex-checker.php [OPTIONS]\n";
    echo "Validate LINE WORKS Flex Message JSON\n\n";
    echo "Options:\n";
    echo "  -f <file>     Input JSON file to validate\n";
    echo "  -j <json>     JSON string to validate\n";
    echo "  -v            Verbose output (show details even if valid)\n";
    echo "  -h            Show this help message\n\n";
    echo "Examples:\n";
    echo "  php flex-checker.php -f message.json\n";
    echo "  php flex-checker.php -j '{\"type\":\"flex\",\"altText\":\"test\",\"contents\":{...}}'\n";
    echo "  echo '{...}' | php flex-checker.php\n";
    exit(0);
}

// Parse command line arguments
$options = getopt("f:j:vh", [], $optind);

if (isset($options['h'])) {
    showHelp();
}

$verbose = isset($options['v']);
$jsonData = null;

// Get JSON data from various sources
if (isset($options['f'])) {
    // From file
    $file = $options['f'];
    if (!file_exists($file)) {
        echo "Error: File '{$file}' not found\n";
        exit(1);
    }
    
    $jsonData = file_get_contents($file);
    if ($jsonData === false) {
        echo "Error: Could not read file '{$file}'\n";
        exit(1);
    }
} elseif (isset($options['j'])) {
    // From command line argument
    $jsonData = $options['j'];
} else {
    // From stdin
    $jsonData = stream_get_contents(STDIN);
    if (empty(trim($jsonData))) {
        echo "Error: No JSON data provided\n\n";
        showHelp();
    }
}

// Parse JSON
$data = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON - " . json_last_error_msg() . "\n";
    exit(1);
}

// Validate
$validator = new FlexValidator();
$errors = $validator->validate($data);

// Output results
if (empty($errors)) {
    echo "✅ Valid Flex Message\n";
    
    if ($verbose) {
        echo "\nMessage Details:\n";
        echo "- Type: " . ($data['type'] ?? 'unknown') . "\n";
        echo "- Alt Text Length: " . mb_strlen($data['altText'] ?? '', 'UTF-8') . " characters\n";
        
        if ($data['type'] === 'flex' && isset($data['contents'])) {
            echo "- Contents Type: " . ($data['contents']['type'] ?? 'unknown') . "\n";
            
            if (($data['contents']['type'] ?? '') === 'carousel') {
                $bubbleCount = count($data['contents']['contents'] ?? []);
                echo "- Bubble Count: {$bubbleCount}\n";
            }
        } elseif ($data['type'] === 'carousel') {
            $bubbleCount = count($data['contents'] ?? []);
            echo "- Bubble Count: {$bubbleCount}\n";
        }
        
        $sizeCalculator = new \MdFlex\SizeCalculator();
        $totalSize = $sizeCalculator->jsonSize($data);
        echo "- Total Size: {$totalSize} bytes\n";
    }
} else {
    echo "❌ Invalid Flex Message\n\n";
    echo "Errors found:\n";
    foreach ($errors as $i => $error) {
        echo sprintf("%2d. %s\n", $i + 1, $error);
    }
    exit(1);
}