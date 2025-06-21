<?php

declare(strict_types=1);

namespace Kijtkd\Tests;

use Kijtkd\MarkdownFlexConverter;
use Kijtkd\Theme\DarkTheme;
use PHPUnit\Framework\TestCase;

final class MarkdownFlexConverterTest extends TestCase
{
    private MarkdownFlexConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new MarkdownFlexConverter();
    }

    public function testConvertSimpleHeading(): void
    {
        $markdown = '# Hello World';
        [$json, $altText] = $this->converter->convert($markdown);

        $this->assertIsArray($json);
        $this->assertEquals('bubble', $json['type']);
        $this->assertArrayHasKey('body', $json);
        $this->assertEquals('Hello World', $altText);
    }

    public function testConvertParagraph(): void
    {
        $markdown = 'This is a simple paragraph.';
        [$json, $altText] = $this->converter->convert($markdown);

        $this->assertIsArray($json);
        $this->assertEquals('This is a simple paragraph.', $altText);
    }

    public function testConvertList(): void
    {
        $markdown = "- Item 1\n- Item 2\n- Item 3";
        [$json, $altText] = $this->converter->convert($markdown);

        $this->assertIsArray($json);
        $this->assertStringContainsString('Item 1', $altText);
        $this->assertStringContainsString('Item 2', $altText);
        $this->assertStringContainsString('Item 3', $altText);
    }

    public function testConvertCodeBlock(): void
    {
        $markdown = "```php\necho 'Hello World';\n```";
        [$json, $altText] = $this->converter->convert($markdown);

        $this->assertIsArray($json);
        $this->assertStringContainsString("echo 'Hello World';", $altText);
    }

    public function testConvertBlockQuote(): void
    {
        $markdown = '> This is a quote';
        [$json, $altText] = $this->converter->convert($markdown);

        $this->assertIsArray($json);
        $this->assertEquals('This is a quote', $altText);
    }

    public function testSetTheme(): void
    {
        $converter = $this->converter->setTheme(new DarkTheme());
        
        $markdown = '> This is a quote';
        [$json, $altText] = $converter->convert($markdown);

        $this->assertIsArray($json);
    }

    public function testLongTextTruncation(): void
    {
        $longText = str_repeat('This is a very long text. ', 100);
        $markdown = "# Heading\n\n" . $longText;
        
        [$json, $altText] = $this->converter->convert($markdown);

        $this->assertIsArray($json);
        $this->assertLessThanOrEqual(400, mb_strlen($altText, 'UTF-8'));
    }

    public function testMultipleBubbles(): void
    {
        $markdown = '';
        for ($i = 1; $i <= 20; $i++) {
            $markdown .= "# Heading $i\n\n" . str_repeat("This is paragraph $i. ", 50) . "\n\n";
        }

        [$json, $altText] = $this->converter->convert($markdown);

        $this->assertIsArray($json);
        
        // Should create a carousel due to size
        if (isset($json['type']) && $json['type'] === 'carousel') {
            $this->assertArrayHasKey('contents', $json);
            $this->assertIsArray($json['contents']);
            $this->assertLessThanOrEqual(10, count($json['contents']));
        }
    }
}