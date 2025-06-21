<?php

declare(strict_types=1);

namespace Kijtkd\Parser;

use League\CommonMark\Node\Block\Document;

interface ParserInterface
{
    public function parse(string $markdown): Document;
}