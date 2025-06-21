<?php

declare(strict_types=1);

namespace Kijtkd;

final class BubbleBuilder
{
    private const BUBBLE_SIZE_LIMIT = 10240; // 10 KB
    
    private array $components = [];
    private SizeCalculator $sizeCalculator;
    private array $bubbles = [];

    public function __construct(SizeCalculator $sizeCalculator)
    {
        $this->sizeCalculator = $sizeCalculator;
    }

    public function add(array $component): void
    {
        if ($this->wouldExceed($component)) {
            $this->flush();
        }
        
        $this->components[] = $component;
    }

    private function wouldExceed(array $component): bool
    {
        if (empty($this->components)) {
            return false;
        }
        
        $testComponents = $this->components;
        $testComponents[] = $component;

        $testBubble = $this->createBubble($testComponents);
        
        return $this->sizeCalculator->jsonSize($testBubble) > self::BUBBLE_SIZE_LIMIT;
    }

    public function flush(): void
    {
        if (empty($this->components)) {
            return;
        }

        $this->bubbles[] = $this->createBubble($this->components);
        $this->components = [];
    }

    public function getBubbles(): array
    {
        $this->flush();
        return $this->bubbles;
    }

    private function createBubble(array $components): array
    {
        return [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => $components,
            ],
        ];
    }

    public function reset(): void
    {
        $this->components = [];
        $this->bubbles = [];
    }
}