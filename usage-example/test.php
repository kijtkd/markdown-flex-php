<?php

require_once __DIR__ . '/vendor/autoload.php';

use MdFlex\MarkdownFlexConverter;

$markdown = '# Hello World
This is a test of the local package.';

$converter = new MarkdownFlexConverter();
[$json, $altText] = $converter->convert($markdown);

echo "Alt Text: " . $altText . "\n";
echo "JSON: " . json_encode($json, JSON_PRETTY_PRINT) . "\n";