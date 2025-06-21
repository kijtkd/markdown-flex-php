<?php

declare(strict_types=1);

namespace Kijtkd\Parser;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Parser\MarkdownParser;

final class CommonMarkParser implements ParserInterface
{
    private MarkdownParser $parser;

    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        
        $this->parser = new MarkdownParser($environment);
    }

    public function parse(string $markdown): Document
    {
        return $this->parser->parse($markdown);
    }
}