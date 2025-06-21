<?php

declare(strict_types=1);

namespace Kijtkd;

final class CarouselBuilder
{
    private const MAX_BUBBLES = 10;
    private const CAROUSEL_SIZE_LIMIT = 51200; // 50 KB
    private const ALT_TEXT_LIMIT = 400;
    
    private BubbleBuilder $bubbleBuilder;
    private SizeCalculator $sizeCalculator;
    private array $plainTextParts = [];
    private string $currentPlainText = '';

    public function __construct(
        BubbleBuilder $bubbleBuilder,
        SizeCalculator $sizeCalculator
    ) {
        $this->bubbleBuilder = $bubbleBuilder;
        $this->sizeCalculator = $sizeCalculator;
    }

    public function addComponent(array $component): void
    {
        $this->bubbleBuilder->add($component);
    }

    public function addPlainText(string $text): void
    {
        $this->plainTextParts[] = $text;
    }

    public function build(): array
    {
        $bubbles = $this->bubbleBuilder->getBubbles();
        
        if (empty($bubbles)) {
            return [
                'json' => [],
                'altText' => '',
            ];
        }

        // Single bubble case
        if (count($bubbles) === 1) {
            return [
                'json' => $bubbles[0],
                'altText' => $this->generateAltText(),
            ];
        }

        // Multiple bubbles - create carousel
        $carousel = $this->createCarousel($bubbles);
        
        return [
            'json' => $carousel,
            'altText' => $this->generateAltText(),
        ];
    }

    private function createCarousel(array $bubbles): array
    {
        // Limit to MAX_BUBBLES
        $bubbles = array_slice($bubbles, 0, self::MAX_BUBBLES);
        
        $carousel = [
            'type' => 'carousel',
            'contents' => $bubbles,
        ];

        // Check total size
        while ($this->sizeCalculator->jsonSize($carousel) > self::CAROUSEL_SIZE_LIMIT && count($bubbles) > 1) {
            array_pop($bubbles);
            $carousel['contents'] = $bubbles;
        }

        return $carousel;
    }

    private function generateAltText(): string
    {
        $altText = implode(' ', $this->plainTextParts);
        $altText = preg_replace('/\s+/', ' ', $altText);
        $altText = trim($altText);
        
        if (mb_strlen($altText, 'UTF-8') > self::ALT_TEXT_LIMIT) {
            $altText = mb_strimwidth($altText, 0, self::ALT_TEXT_LIMIT - 3, '...', 'UTF-8');
        }

        return $altText;
    }

    public function reset(): void
    {
        $this->bubbleBuilder->reset();
        $this->plainTextParts = [];
        $this->currentPlainText = '';
    }
}