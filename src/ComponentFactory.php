<?php

declare(strict_types=1);

namespace MdFlex;

use League\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Node\Block\ListItem;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Node;
use MdFlex\Theme\ThemeInterface;

final class ComponentFactory
{
    private const MAX_TEXT_LENGTH = 2000;
    
    private ThemeInterface $theme;
    private SizeCalculator $sizeCalculator;
    private array $options;

    public function __construct(
        ThemeInterface $theme,
        SizeCalculator $sizeCalculator,
        array $options = []
    ) {
        $this->theme = $theme;
        $this->sizeCalculator = $sizeCalculator;
        $this->options = $options;
    }

    public function createComponent(Node $node): ?array
    {
        return match (true) {
            $node instanceof Heading => $this->createHeading($node),
            $node instanceof Paragraph => $this->createParagraph($node),
            $node instanceof ListBlock => $this->createList($node),
            $node instanceof Image => $this->createImage($node),
            $node instanceof BlockQuote => $this->createBlockQuote($node),
            $node instanceof FencedCode => $this->createCodeBlock($node),
            default => null,
        };
    }

    private function createHeading(Heading $heading): array
    {
        $text = $this->extractText($heading);
        
        return [
            'type' => 'text',
            'text' => $this->truncateText($text),
            'size' => $this->theme->getHeadingSize($heading->getLevel()),
            'weight' => $this->theme->getHeadingWeight(),
            'wrap' => true,
        ];
    }

    private function createParagraph(Paragraph $paragraph): array
    {
        $text = $this->extractText($paragraph);
        
        return [
            'type' => 'text',
            'text' => $this->truncateText($text),
            'size' => $this->theme->getTextSize(),
            'wrap' => true,
        ];
    }

    private function createList(ListBlock $list): array
    {
        $items = [];
        
        foreach ($list->children() as $item) {
            if ($item instanceof ListItem) {
                $items[] = $this->createListItem($item);
            }
        }
        
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'spacing' => 'sm',
            'contents' => $items,
        ];
    }

    private function createListItem(ListItem $item): array
    {
        $text = $this->extractText($item);
        
        return [
            'type' => 'box',
            'layout' => 'baseline',
            'spacing' => 'sm',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => '•',
                    'size' => $this->theme->getTextSize(),
                    'flex' => 0,
                ],
                [
                    'type' => 'text',
                    'text' => $this->truncateText($text),
                    'size' => $this->theme->getTextSize(),
                    'wrap' => true,
                    'flex' => 1,
                ],
            ],
        ];
    }

    private function createImage(Image $image): array
    {
        return [
            'type' => 'image',
            'url' => $image->getUrl(),
            'aspectRatio' => $this->theme->getImageRatio(),
            'aspectMode' => 'cover',
            'size' => 'full',
        ];
    }

    private function createBlockQuote(BlockQuote $quote): array
    {
        $text = $this->extractText($quote);
        
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'backgroundColor' => $this->theme->getQuoteBackgroundColor(),
            'paddingAll' => 'md',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $this->truncateText($text),
                    'size' => $this->theme->getTextSize(),
                    'wrap' => true,
                ],
            ],
        ];
    }

    private function createCodeBlock(FencedCode $code): array
    {
        $content = $code->getLiteral();
        
        if ($this->options['code_img'] ?? false) {
            // TODO: Implement image generation
            return $this->createCodeAsText($content);
        }
        
        return $this->createCodeAsText($content);
    }

    private function createCodeAsText(string $code): array
    {
        $lines = explode("\n", $code);
        $components = [];
        
        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }
            
            $components[] = [
                'type' => 'text',
                'text' => $this->truncateText($line),
                'size' => $this->theme->getCodeSize(),
                'wrap' => false,
            ];
        }
        
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'spacing' => 'none',
            'backgroundColor' => '#F6F8FA',
            'paddingAll' => 'sm',
            'contents' => $components,
        ];
    }

    private function extractText(Node $node): string
    {
        $text = '';
        
        foreach ($node->children() as $child) {
            if ($child instanceof Text) {
                $text .= $child->getLiteral();
            } elseif ($child instanceof Strong) {
                $text .= $this->extractText($child);
            } elseif ($child instanceof Emphasis) {
                $text .= $this->extractText($child);
            } else {
                $text .= $this->extractText($child);
            }
        }
        
        return trim($text);
    }

    private function truncateText(string $text): string
    {
        if ($this->sizeCalculator->textSize($text) <= self::MAX_TEXT_LENGTH) {
            return $text;
        }
        
        return mb_strimwidth($text, 0, self::MAX_TEXT_LENGTH - 3, '...', 'UTF-8');
    }
}