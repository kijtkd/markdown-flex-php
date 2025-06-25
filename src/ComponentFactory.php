<?php

declare(strict_types=1);

namespace Kijtkd;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableSection;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use Kijtkd\Theme\ThemeInterface;

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
            $node instanceof IndentedCode => $this->createIndentedCodeBlock($node),
            $node instanceof ThematicBreak => $this->createThematicBreak($node),
            $node instanceof Code => $this->createInlineCode($node),
            $node instanceof Link => $this->createLink($node),
            $node instanceof Table => $this->createTable($node),
            default => null,
        };
    }

    private function createHeading(Heading $heading): array
    {
        $text = $this->extractText($heading);
        
        $component = [
            'type' => 'text',
            'text' => $this->truncateText($text),
            'size' => $this->theme->getHeadingSize($heading->getLevel()),
            'weight' => $this->theme->getHeadingWeight(),
            'wrap' => true,
        ];

        // Add top margin for h2 and below
        if ($heading->getLevel() > 1) {
            $component['margin'] = 'lg';
        }

        return $component;
    }

    private function createParagraph(Paragraph $paragraph): array
    {
        $contents = $this->extractRichText($paragraph);
        
        if (count($contents) === 1 && is_string($contents[0])) {
            // Simple text without formatting
            return [
                'type' => 'text',
                'text' => $this->truncateText($contents[0]),
                'size' => $this->theme->getTextSize(),
                'wrap' => true,
            ];
        }
        
        // Rich text with formatting
        return [
            'type' => 'text',
            'contents' => $contents,
            'size' => $this->theme->getTextSize(),
            'wrap' => true,
        ];
    }

    private function createList(ListBlock $list): array
    {
        $items = [];
        $isOrdered = $list->getListData()->type === ListBlock::TYPE_ORDERED;
        $counter = $list->getListData()->start ?? 1;
        
        foreach ($list->children() as $item) {
            if ($item instanceof ListItem) {
                $items[] = $this->createListItem($item, $isOrdered, $counter);
                if ($isOrdered) $counter++;
            }
        }
        
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'spacing' => 'sm',
            'contents' => $items,
            'margin' => 'md',
        ];
    }

    private function createListItem(ListItem $item, bool $isOrdered = false, int $number = 1): array
    {
        // Extract paragraph content with rich text support
        $contents = [];
        foreach ($item->children() as $child) {
            if ($child instanceof Paragraph) {
                $richContents = $this->extractRichText($child);
                if (count($richContents) === 1 && is_string($richContents[0])) {
                    $contents = [
                        'type' => 'text',
                        'text' => $this->truncateText($richContents[0]),
                        'size' => $this->theme->getTextSize(),
                        'wrap' => true,
                        'flex' => 1,
                        'margin' => 'sm',
                    ];
                } else {
                    $contents = [
                        'type' => 'text',
                        'contents' => $richContents,
                        'size' => $this->theme->getTextSize(),
                        'wrap' => true,
                        'flex' => 1,
                        'margin' => 'sm',
                    ];
                }
                break;
            }
            // Skip nested lists - they will be processed separately
        }
        
        if (empty($contents)) {
            $contents = [
                'type' => 'text',
                'text' => '',
                'size' => $this->theme->getTextSize(),
                'wrap' => true,
                'flex' => 1,
                'margin' => 'sm',
            ];
        }
        
        $marker = $isOrdered ? "{$number}." : '•';
        
        return [
            'type' => 'box',
            'layout' => 'baseline',
            'spacing' => 'sm',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $marker,
                    'size' => $this->theme->getTextSize(),
                    'color' => '#666666',
                    'flex' => 0,
                    'margin' => 'none',
                ],
                $contents,
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
        // 引用内のリッチテキストを処理
        $richContents = [];
        foreach ($quote->children() as $child) {
            if ($child instanceof Paragraph) {
                $richContents = array_merge($richContents, $this->extractRichText($child));
            }
        }
        
        $textComponent = [
            'type' => 'text',
            'size' => $this->theme->getTextSize(),
            'wrap' => true,
            'style' => 'italic',
        ];
        
        if (count($richContents) === 1 && is_string($richContents[0])) {
            $textComponent['text'] = $this->truncateText($richContents[0]);
        } else {
            $textComponent['contents'] = $richContents;
        }
        
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'backgroundColor' => $this->theme->getQuoteBackgroundColor(),
            'paddingAll' => 'md',
            'margin' => 'md',
            'cornerRadius' => '4px',
            'borderWidth' => '2px',
            'borderColor' => '#E0E0E0',
            'contents' => [$textComponent],
        ];
    }

    private function createCodeBlock(FencedCode $code): array
    {
        $content = $code->getLiteral();
        $language = $code->getInfo() ?? '';
        
        if ($this->options['code_img'] ?? false) {
            // TODO: Implement image generation
            return $this->createCodeAsText($content, $language);
        }
        
        return $this->createCodeAsText($content, $language);
    }

    private function createCodeAsText(string $code, string $language = ''): array
    {
        $lines = explode("\n", rtrim($code));
        $components = [];
        
        // Add language label if provided
        if (!empty($language)) {
            $components[] = [
                'type' => 'text',
                'text' => $language,
                'size' => 'xs',
                'color' => '#888888',
                'weight' => 'bold',
                'margin' => 'none',
            ];
        }
        
        foreach ($lines as $line) {
            $components[] = [
                'type' => 'text',
                'text' => empty($line) ? ' ' : $this->truncateText($line),
                'size' => $this->theme->getCodeSize(),
                'wrap' => false,
                'color' => '#333333',
                'fontFamily' => 'monospace',
            ];
        }
        
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'spacing' => 'xs',
            'backgroundColor' => '#F6F8FA',
            'paddingAll' => 'md',
            'margin' => 'md',
            'cornerRadius' => '4px',
            'contents' => $components,
        ];
    }

    private function extractText(Node $node): string
    {
        $text = '';
        
        foreach ($node->children() as $child) {
            if ($child instanceof Text) {
                $literal = $child->getLiteral();
                // 改行を適切に処理：連続する改行を単一スペースに変換
                $literal = preg_replace('/\s+/', ' ', $literal);
                $text .= $literal;
            } elseif ($child instanceof Code) {
                // インラインコードの内容を含める
                $text .= '`' . $child->getLiteral() . '`';
            } elseif ($child instanceof Strong) {
                $text .= $this->extractText($child);
            } elseif ($child instanceof Emphasis) {
                $text .= $this->extractText($child);
            } elseif ($child instanceof Image) {
                // 画像の場合はマークダウン形式で表現
                $text .= '![' . ($child->getTitle() ?? '') . '](' . $child->getUrl() . ')';
            } elseif ($child instanceof Link) {
                // リンクの場合はテキスト部分のみを取得
                $text .= $this->extractText($child);
            } else {
                $text .= $this->extractText($child);
            }
        }
        
        return trim($text);
    }

    private function createIndentedCodeBlock(IndentedCode $code): array
    {
        return $this->createCodeAsText($code->getLiteral());
    }

    private function createThematicBreak(ThematicBreak $break): array
    {
        return [
            'type' => 'separator',
            'margin' => 'lg',
            'color' => '#E0E0E0',
        ];
    }

    private function createInlineCode(Code $code): array
    {
        return [
            'type' => 'text',
            'text' => $code->getLiteral(),
            'size' => $this->theme->getCodeSize(),
            'backgroundColor' => '#F6F8FA',
            'color' => '#D73A49',
            'wrap' => false,
        ];
    }

    private function createLink(Link $link): array
    {
        $text = $this->extractText($link);
        $url = $link->getUrl();
        
        return [
            'type' => 'text',
            'text' => $this->truncateText($text),
            'size' => $this->theme->getTextSize(),
            'color' => '#1155CC',
            'action' => [
                'type' => 'uri',
                'uri' => $url,
            ],
        ];
    }

    private function extractRichText(Node $node): array
    {
        $contents = [];
        $currentText = '';
        
        foreach ($node->children() as $child) {
            if ($child instanceof Text) {
                $currentText .= $child->getLiteral();
            } elseif ($child instanceof Strong) {
                if (!empty($currentText)) {
                    $contents[] = [
                        'type' => 'span',
                        'text' => $currentText,
                    ];
                    $currentText = '';
                }
                // Strong内にリンクがある場合の処理
                $strongContents = $this->extractRichText($child);
                foreach ($strongContents as $strongContent) {
                    if (isset($strongContent['action'])) {
                        // リンクがある場合は太字とリンクの両方を適用
                        $strongContent['weight'] = 'bold';
                    } else {
                        $strongContent['weight'] = 'bold';
                    }
                    $contents[] = $strongContent;
                }
            } elseif ($child instanceof Emphasis) {
                if (!empty($currentText)) {
                    $contents[] = [
                        'type' => 'span',
                        'text' => $currentText,
                    ];
                    $currentText = '';
                }
                // Emphasis内にリンクがある場合の処理
                $emphasisContents = $this->extractRichText($child);
                foreach ($emphasisContents as $emphasisContent) {
                    if (isset($emphasisContent['action'])) {
                        // リンクがある場合は斜体とリンクの両方を適用
                        $emphasisContent['style'] = 'italic';
                    } else {
                        $emphasisContent['style'] = 'italic';
                    }
                    $contents[] = $emphasisContent;
                }
            } elseif ($child instanceof Code) {
                if (!empty($currentText)) {
                    $contents[] = [
                        'type' => 'span',
                        'text' => $currentText,
                    ];
                    $currentText = '';
                }
                $contents[] = [
                    'type' => 'span',
                    'text' => $child->getLiteral(),
                    'color' => '#D73A49',
                    'size' => $this->theme->getCodeSize(),
                ];
            } elseif ($child instanceof Link) {
                if (!empty($currentText)) {
                    $contents[] = [
                        'type' => 'span',
                        'text' => $currentText,
                    ];
                    $currentText = '';
                }
                $contents[] = [
                    'type' => 'span',
                    'text' => $this->extractText($child),
                    'color' => '#1155CC',
                    'action' => [
                        'type' => 'uri',
                        'uri' => $child->getUrl(),
                    ],
                ];
            } else {
                $currentText .= $this->extractText($child);
            }
        }
        
        if (!empty($currentText)) {
            $contents[] = [
                'type' => 'span',
                'text' => $currentText,
            ];
        }
        
        return empty($contents) ? [['type' => 'span', 'text' => '']] : $contents;
    }

    private function createTable(Table $table): array
    {
        $rows = [];
        $isFirstSection = true;
        
        foreach ($table->children() as $section) {
            if ($section instanceof TableSection) {
                $isHeader = $isFirstSection && $section->isHead();
                foreach ($section->children() as $row) {
                    if ($row instanceof TableRow) {
                        $tableRow = $this->createTableRow($row, $isHeader);
                        if (!empty($tableRow)) {
                            $rows[] = $tableRow;
                        }
                        $isHeader = false; // Only first row in head section is header
                    }
                }
                $isFirstSection = false;
            }
        }
        
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'margin' => 'md',
            'spacing' => 'sm',
            'contents' => $rows,
        ];
    }

    private function createTableRow(TableRow $row, bool $isHeader = false): array
    {
        $cells = [];
        
        foreach ($row->children() as $cell) {
            if ($cell instanceof TableCell) {
                $tableCell = $this->createTableCell($cell, $isHeader);
                if ($tableCell !== null) {
                    $cells[] = $tableCell;
                }
            }
        }
        
        if (empty($cells)) {
            return [];
        }
        
        $rowBox = [
            'type' => 'box',
            'layout' => 'horizontal',
            'spacing' => 'md',
            'contents' => $cells,
        ];
        
        if ($isHeader) {
            $rowBox['paddingBottom'] = 'sm';
            // Add separator after header
            return [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'xs',
                'contents' => [
                    $rowBox,
                    [
                        'type' => 'separator',
                        'color' => '#E0E0E0',
                    ]
                ],
            ];
        }
        
        return $rowBox;
    }

    private function createTableCell(TableCell $cell, bool $isHeader = false): array
    {
        $text = $this->extractText($cell);
        
        if (empty(trim($text))) {
            $text = ' '; // Empty cell placeholder
        }
        
        $cellComponent = [
            'type' => 'text',
            'text' => $this->truncateText($text),
            'size' => $isHeader ? 'sm' : 'xs',
            'wrap' => true,
            'flex' => 1,
        ];
        
        if ($isHeader) {
            $cellComponent['weight'] = 'bold';
            $cellComponent['color'] = '#333333';
        } else {
            $cellComponent['color'] = '#666666';
        }
        
        // Handle alignment - check if method exists
        if (method_exists($cell, 'getAlignment') && $cell->getAlignment() !== null) {
            switch ($cell->getAlignment()) {
                case 'left':
                    $cellComponent['align'] = 'start';
                    break;
                case 'center':
                    $cellComponent['align'] = 'center';
                    break;
                case 'right':
                    $cellComponent['align'] = 'end';
                    break;
            }
        }
        
        return $cellComponent;
    }

    private function truncateText(string $text): string
    {
        if ($this->sizeCalculator->textSize($text) <= self::MAX_TEXT_LENGTH) {
            return $text;
        }
        
        return mb_strimwidth($text, 0, self::MAX_TEXT_LENGTH - 3, '...', 'UTF-8');
    }
}