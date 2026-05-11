<?php

namespace Tests\Unit;

use App\Modules\Parser\MassSnippetFileParser;
use Tests\TestCase;

class MassSnippetFileParserTest extends TestCase
{
    public function test_parser_extracts_markdown_frontmatter(): void
    {
        $parser = new MassSnippetFileParser();

        $content = <<<'MD'
---
title: Example API
language: php
tags: api, laravel
is_public: true
---
<?php
echo "Hello";
MD;

        $item = $parser->parseEntry('imports/example.md', $content);

        $this->assertSame('Example API', $item['title']);
        $this->assertSame('php', $item['language']);
        $this->assertSame(['api', 'laravel'], $item['tags']);
        $this->assertTrue($item['is_public']);
        $this->assertStringContainsString('echo "Hello";', $item['code']);
    }
}
