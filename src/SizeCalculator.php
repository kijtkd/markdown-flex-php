<?php

declare(strict_types=1);

namespace MdFlex;

final class SizeCalculator
{
    public function jsonSize(array $data): int
    {
        return strlen(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function textSize(string $text): int
    {
        return mb_strlen($text, 'UTF-8');
    }

    public function byteSize(string $text): int
    {
        return strlen($text);
    }
}