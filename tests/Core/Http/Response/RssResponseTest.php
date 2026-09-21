<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use InvalidArgumentException;
use Nqphp\Core\Http\Response\RssResponse;
use PHPUnit\Framework\TestCase;

class RssResponseTest extends TestCase
{
    public function testRendersValidRssFeedWithoutItems(): void
    {
        $response = new RssResponse(
            'My Tech Blog',
            'https://example.com',
            'Latest news and tutorials',
            [],
            200,
            [],
            'ru'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/rss+xml; charset=UTF-8', $response->headers->get('Content-Type'));

        $content = (string) $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<rss version="2.0">', $content);
        $this->assertStringContainsString('<title>My Tech Blog</title>', $content);
        $this->assertStringContainsString('<link>https://example.com</link>', $content);
        $this->assertStringContainsString('<language>ru</language>', $content);
    }

    public function testRendersItemsWithEscapedContent(): void
    {
        $items = [
            [
                'title' => 'Release & Update <v1.0>',
                'link' => 'https://example.com/posts/1',
                'description' => 'Important notes & features',
                'pubDate' => 'Mon, 21 Sep 2026 08:00:00 GMT',
                'guid' => 'https://example.com/posts/1',
                'author' => 'editor@example.com',
            ],
        ];

        $response = new RssResponse(
            'News',
            'https://example.com',
            'Feed description',
            $items
        );

        $content = (string) $response->getContent();
        $this->assertStringContainsString('<title>Release &amp; Update &lt;v1.0&gt;</title>', $content);
        $this->assertStringContainsString('<description>Important notes &amp; features</description>', $content);
        $this->assertStringContainsString('<pubDate>Mon, 21 Sep 2026 08:00:00 GMT</pubDate>', $content);
        $this->assertStringContainsString('<guid>https://example.com/posts/1</guid>', $content);
        $this->assertStringContainsString('<author>editor@example.com</author>', $content);
    }

    public function testThrowsOnEmptyTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RSS title cannot be empty.');
        new RssResponse('', 'https://example.com', 'Description');
    }

    public function testThrowsOnEmptyLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RSS link cannot be empty.');
        new RssResponse('Title', '   ', 'Description');
    }
}
