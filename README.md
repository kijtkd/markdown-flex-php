# markdown-flex-php

Convert Markdown to LINE WORKS Bot Flexible Template

## Installation

Add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../markdown-flex-php"
        }
    ],
    "require": {
        "kijtkd/markdown-flex-php": "*"
    }
}
```

Then run:

```bash
composer install
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

### CLI Usage

```bash
# Convert Markdown file to Flex JSON
php markdown-flex.php -f input.md

# Save to file
php markdown-flex.php -f input.md -o output.json

# Use dark theme
php markdown-flex.php -f input.md -t dark

# Validate Flex JSON
php flex-checker.php -f message.json
php flex-checker.php -v  # Verbose mode
cat message.json | php flex-checker.php  # From stdin
```

## Features

- **Automatic Size Management**: Automatically handles LINE WORKS Bubble (10KB) and Carousel (50KB) size limits
- **Text Truncation**: Automatically truncates text to 2,000 characters and altText to 400 characters
- **Theme Support**: Customizable themes for different visual styles
- **Markdown Support**: Supports headings, paragraphs, lists, code blocks, blockquotes, images, tables, and rich text formatting
- **CLI Tools**: Command-line converter and validator tools
- **Flexible Installation**: Works without Packagist registration
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
| `\| Table \|` | `box` + `text` | Headers with separators |
| `**Bold**` | Rich text | Span with weight: bold |
| `*Italic*` | Rich text | Span with style: italic |

## LINE WORKS Limitations

The library automatically handles these LINE WORKS limitations:

- **Bubble Size**: ≤ 10KB (automatically splits into multiple bubbles)
- **Text Length**: ≤ 2,000 characters (automatically truncates)
- **AltText Length**: ≤ 400 characters (automatically truncates)
- **Carousel**: ≤ 10 bubbles and ≤ 50KB total

## Architecture

### Core Classes
- **MarkdownFlexConverter**: Main facade class
- **ComponentFactory**: Converts Markdown AST to Flex components
- **BubbleBuilder**: Manages 10KB bubble size limits
- **CarouselBuilder**: Handles multiple bubbles and 50KB carousel limits
- **FlexValidator**: Validates Flex messages against LINE WORKS specifications
- **Theme System**: DefaultTheme and DarkTheme support

### Automatic Size Management
- **Bubble Splitting**: Content over 10KB automatically splits into multiple bubbles
- **Carousel Creation**: Multiple bubbles become carousel (max 10 bubbles, 50KB total)
- **Text Truncation**: Text over 2,000 characters truncated with ellipsis
- **AltText Limiting**: AltText automatically truncated to 400 characters

## Requirements

- PHP 8.1+
- league/commonmark ^2.4
- league/commonmark-extension-table (for table support)

## Validation

The library includes a comprehensive validator that checks:
- Flex message structure compliance
- Size limits (bubble, carousel, text)
- Required properties
- Property value constraints
- Rich text format validation

```bash
# Validate generated Flex JSON
php flex-checker.php -f output.json

# Validate with verbose error reporting
php flex-checker.php -f output.json -v

# All sample files pass validation
php flex-checker.php -f samples/conference.json
```

## License

MIT License - see [LICENSE](LICENSE) file for details.