<?php

declare(strict_types=1);

namespace Kijtkd;

use League\CommonMark\Node\Node;
use League\CommonMark\Node\NodeWalker;
use League\CommonMark\Node\NodeWalkerEvent;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableCell;

final class NodeVisitor
{
    private ComponentFactory $componentFactory;
    private CarouselBuilder $carouselBuilder;

    public function __construct(
        ComponentFactory $componentFactory,
        CarouselBuilder $carouselBuilder
    ) {
        $this->componentFactory = $componentFactory;
        $this->carouselBuilder = $carouselBuilder;
    }

    public function visit(Node $document): array
    {
        $walker = $document->walker();

        while ($event = $walker->next()) {
            if (!$event->isEntering()) {
                continue;
            }

            $node = $event->getNode();
            
            // Skip document, inline nodes, list items, and table components (they're handled by their parent blocks)
            if ($node instanceof \League\CommonMark\Node\Block\Document || 
                $node instanceof AbstractInline ||
                $node instanceof ListItem ||
                $node instanceof TableRow ||
                $node instanceof TableCell) {
                continue;
            }
            
            $component = $this->componentFactory->createComponent($node);

            if ($component !== null) {
                $this->carouselBuilder->addComponent($component);
                
                // Extract plain text for altText
                $plainText = $this->extractPlainText($node);
                if (!empty($plainText)) {
                    $this->carouselBuilder->addPlainText($plainText);
                }
            }
        }

        return $this->carouselBuilder->build();
    }

    private function extractPlainText(Node $node): string
    {
        $text = '';
        $walker = $node->walker();

        while ($event = $walker->next()) {
            if ($event->isEntering()) {
                $currentNode = $event->getNode();
                
                if (method_exists($currentNode, 'getLiteral')) {
                    $literal = $currentNode->getLiteral();
                    if (is_string($literal)) {
                        $text .= $literal . ' ';
                    }
                }
            }
        }

        return trim($text);
    }
}