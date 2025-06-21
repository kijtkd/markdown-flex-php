<?php

declare(strict_types=1);

namespace MdFlex\Tests;

use MdFlex\SizeCalculator;
use PHPUnit\Framework\TestCase;

final class SizeCalculatorTest extends TestCase
{
    private SizeCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SizeCalculator();
    }

    public function testJsonSize(): void
    {
        $data = ['type' => 'text', 'text' => 'Hello'];
        $size = $this->calculator->jsonSize($data);
        
        $expectedJson = '{"type":"text","text":"Hello"}';
        $this->assertEquals(strlen($expectedJson), $size);
    }

    public function testJsonSizeWithUnicode(): void
    {
        $data = ['text' => 'こんにちは'];
        $size = $this->calculator->jsonSize($data);
        
        $expectedJson = '{"text":"こんにちは"}';
        $this->assertEquals(strlen($expectedJson), $size);
    }

    public function testTextSize(): void
    {
        $text = 'Hello World';
        $this->assertEquals(11, $this->calculator->textSize($text));
    }

    public function testTextSizeWithMultibyte(): void
    {
        $text = 'こんにちは';
        $this->assertEquals(5, $this->calculator->textSize($text));
    }

    public function testByteSize(): void
    {
        $text = 'Hello';
        $this->assertEquals(5, $this->calculator->byteSize($text));
    }

    public function testByteSizeWithMultibyte(): void
    {
        $text = 'こんにちは';
        $this->assertEquals(15, $this->calculator->byteSize($text)); // 3 bytes per character
    }
}