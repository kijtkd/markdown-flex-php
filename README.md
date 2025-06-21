# markdown-flex-php

Convert Markdown to LINE WORKS Bot Flexible Template

## Installation

```bash
composer require mdflex/markdown-flex-php
```

## Usage

### Basic Usage

```php
<?php

use Kijtkd\MarkdownFlexConverter;

$converter = new MarkdownFlexConverter();
[$json, $altText] = $converter->convert($markdown);

// Send to LINE WORKS Bot
$client->post('/messages', [
    'altText' => $altText,
    'content' => $json,
]);
```

### With Theme

```php
use Kijtkd\MarkdownFlexConverter;
use Kijtkd\Theme\DarkTheme;

$converter = (new MarkdownFlexConverter())
    ->setTheme(new DarkTheme());

[$json, $altText] = $converter->convert($markdown);
```

### With Options

```php
$converter = (new MarkdownFlexConverter())
    ->setOptions([
        'code_img'  => true,  // Convert code blocks to images
        'max_lines' => 18,    // Max lines per bubble
    ]);

[$json, $altText] = $converter->convert($markdown);
```

## Features

- **Automatic Size Management**: Automatically handles LINE WORKS Bubble (10KB) and Carousel (50KB) size limits
- **Text Truncation**: Automatically truncates text to 2,000 characters and altText to 400 characters
- **Theme Support**: Customizable themes for different visual styles
- **Markdown Support**: Supports headings, paragraphs, lists, code blocks, blockquotes, images, and basic formatting
- **UTF-8 Safe**: Proper handling of multibyte characters

## Supported Markdown Elements

| Markdown Element | Flex Component | Notes |
|-----------------|---------------|-------|
| `# Heading` | `text` | Sizes: xxl, xl, lg, md, sm, xs |
| Paragraph | `text` | With text wrapping |
| `- List item` | `box` + `text` | With bullet points |
| `![Image](url)` | `image` | Aspect ratio 20:13 |
| `` `code` `` | `text` | Monospace styling |
| `> Quote` | `box` | With background color |

## LINE WORKS Limitations

The library automatically handles these LINE WORKS limitations:

- **Bubble Size**: ≤ 10KB (automatically splits into multiple bubbles)
- **Text Length**: ≤ 2,000 characters (automatically truncates)
- **AltText Length**: ≤ 400 characters (automatically truncates)
- **Carousel**: ≤ 10 bubbles and ≤ 50KB total

## Requirements

- PHP 8.1+
- league/commonmark ^2.4
- psr/simple-cache ^3.0

## Testing

```bash
composer test
composer phpstan
composer cs
```

## License

MIT License - see [LICENSE](LICENSE) file for details.