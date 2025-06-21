<?php

require_once __DIR__ . '/vendor/autoload.php';

use MdFlex\MarkdownFlexConverter;
use MdFlex\Theme\DarkTheme;

// サンプルMarkdown
$markdown = <<<MD
# LINE WORKS Flex Message Demo

This is a demonstration of converting Markdown to LINE WORKS Flex Template.

## Features

- Automatic size limitation handling
- Multiple theme support
- Code block support
- Image support

### Code Example

```php
\$converter = new MarkdownFlexConverter();
[\$json, \$altText] = \$converter->convert(\$markdown);
```

> This library automatically handles LINE WORKS limitations!

### List Example

1. First item
2. Second item
3. Third item

**Bold text** and *italic text* are also supported.
MD;

// 基本的な使用例
$converter = new MarkdownFlexConverter();
[$json, $altText] = $converter->convert($markdown);

echo "=== Default Theme ===\n";
echo "Alt Text: " . $altText . "\n\n";
echo "JSON Output:\n";
echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// ダークテーマの使用例
$converter->setTheme(new DarkTheme());
[$json2, $altText2] = $converter->convert($markdown);

echo "=== Dark Theme ===\n";
echo "JSON Output:\n";
echo json_encode($json2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";