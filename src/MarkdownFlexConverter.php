<?php

declare(strict_types=1);

namespace Kijtkd;

use Kijtkd\Parser\CommonMarkParser;
use Kijtkd\Parser\ParserInterface;
use Kijtkd\Theme\DefaultTheme;
use Kijtkd\Theme\ThemeInterface;

final class MarkdownFlexConverter
{
    private ParserInterface $parser;
    private ThemeInterface $theme;
    private array $options = [
        'code_img' => false,
        'max_lines' => 18,
    ];

    public function __construct()
    {
        $this->parser = new CommonMarkParser();
        $this->theme = new DefaultTheme();
    }

    public function setParser(ParserInterface $parser): self
    {
        $this->parser = $parser;
        return $this;
    }

    public function setTheme(ThemeInterface $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function convert(string $markdown): array
    {
        // Parse markdown to AST
        $document = $this->parser->parse($markdown);

        // Create necessary components
        $sizeCalculator = new SizeCalculator();
        $componentFactory = new ComponentFactory($this->theme, $sizeCalculator, $this->options);
        $bubbleBuilder = new BubbleBuilder($sizeCalculator);
        $carouselBuilder = new CarouselBuilder($bubbleBuilder, $sizeCalculator);
        $nodeVisitor = new NodeVisitor($componentFactory, $carouselBuilder);

        // Visit nodes and build Flex Message
        $result = $nodeVisitor->visit($document);

        return [
            $result['json'],
            $result['altText'],
        ];
    }
}