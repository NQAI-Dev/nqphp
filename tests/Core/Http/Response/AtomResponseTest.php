<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use InvalidArgumentException;
use Nqphp\Core\Http\Response\AtomResponse;
use PHPUnit\Framework\TestCase;

class AtomResponseTest extends TestCase
{
    public function testRendersValidEmptyFeed(): void
    {
        $response = new AtomResponse(
            'urn:uuid:60a76c80-d399-11d9-b93C-0003939e0af6',
            'Example Feed',
            '2026-09-21T08:45:00Z',
            'https://example.com/feed.atom'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/atom+xml; charset=UTF-8', $response->headers->get('Content-Type'));

        $content = (string) $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom">', $content);
        $this->assertStringContainsString('<id>urn:uuid:60a76c80-d399-11d9-b93C-0003939e0af6</id>', $content);
        $this->assertStringContainsString('<title>Example Feed</title>', $content);
        $this->assertStringContainsString('<updated>2026-09-21T08:45:00Z</updated>', $content);
        $this->assertStringContainsString('<link href="https://example.com/feed.atom" rel="self"/>', $content);
    }

    public function testRendersEntriesWithEscaping(): void
    {
        $entries = [
            [
                'id' => 'urn:uuid:1234',
                'title' => 'Atom & XML <Article>',
                'updated' => '2026-09-21T08:00:00Z',
                'summary' => 'Highlights & overview',
                'link' => 'https://example.com/articles/1',
                'author' => 'Author Name & Co',
            ],
        ];

        $response = new AtomResponse(
            'urn:feed:1',
            'Articles',
            '2026-09-21T08:45:00Z',
            '',
            $entries
        );

        $content = (string) $response->getContent();
        $this->assertStringContainsString('<entry>', $content);
        $this->assertStringContainsString('<id>urn:uuid:1234</id>', $content);
        $this->assertStringContainsString('<title>Atom &amp; XML &lt;Article&gt;</title>', $content);
        $this->assertStringContainsString('<summary>Highlights &amp; overview</summary>', $content);
        $this->assertStringContainsString('<link href="https://example.com/articles/1"/>', $content);
        $this->assertStringContainsString('<name>Author Name &amp; Co</name>', $content);
    }

    public function testThrowsOnEmptyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Atom feed id cannot be empty.');
        new AtomResponse('  ', 'Title', '2026-09-21T00:00:00Z');
    }

    public function testThrowsOnEmptyTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Atom feed title cannot be empty.');
        new AtomResponse('urn:feed:1', '  ', '2026-09-21T00:00:00Z');
    }

    public function testThrowsOnEmptyUpdated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Atom feed updated timestamp cannot be empty.');
        new AtomResponse('urn:feed:1', 'Title', '  ');
    }
}
