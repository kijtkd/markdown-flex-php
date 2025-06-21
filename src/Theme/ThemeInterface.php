<?php

declare(strict_types=1);

namespace MdFlex\Theme;

interface ThemeInterface
{
    public function getHeadingSize(int $level): string;
    
    public function getHeadingWeight(): string;
    
    public function getTextSize(): string;
    
    public function getCodeSize(): string;
    
    public function getQuoteBackgroundColor(): string;
    
    public function getImageRatio(): string;
    
    public function getSpacing(): string;
    
    public function getMargin(): string;
}