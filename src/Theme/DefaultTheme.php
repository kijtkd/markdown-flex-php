<?php

declare(strict_types=1);

namespace Kijtkd\Theme;

class DefaultTheme implements ThemeInterface
{
    private const HEADING_SIZES = [
        1 => 'xxl',
        2 => 'xl',
        3 => 'lg',
        4 => 'md',
        5 => 'sm',
        6 => 'xs',
    ];

    public function getHeadingSize(int $level): string
    {
        return self::HEADING_SIZES[$level] ?? 'md';
    }

    public function getHeadingWeight(): string
    {
        return 'bold';
    }

    public function getTextSize(): string
    {
        return 'sm';
    }

    public function getCodeSize(): string
    {
        return 'xs';
    }

    public function getQuoteBackgroundColor(): string
    {
        return '#F6F8FA';
    }

    public function getImageRatio(): string
    {
        return '20:13';
    }

    public function getSpacing(): string
    {
        return 'md';
    }

    public function getMargin(): string
    {
        return 'md';
    }
}