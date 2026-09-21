<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response representing an RSS 2.0 feed (application/rss+xml).
 */
class RssResponse extends Response
{
    /**
     * @param string $title
     * @param string $link
     * @param string $description
     * @param list<array{title?: string, link?: string, description?: string, pubDate?: string, guid?: string, author?: string}> $items
     * @param int $status
     * @param array<string, string|list<string>> $headers
     * @param string $language
     */
    public function __construct(
        string $title,
        string $link,
        string $description,
        array $items = [],
        int $status = 200,
        array $headers = [],
        string $language = 'en'
    ) {
        if (trim($title) === '') {
            throw new InvalidArgumentException('RSS title cannot be empty.');
        }
        if (trim($link) === '') {
            throw new InvalidArgumentException('RSS link cannot be empty.');
        }

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<rss version="2.0">';
        $xml[] = '  <channel>';
        $xml[] = sprintf('    <title>%s</title>', htmlspecialchars($title, ENT_XML1, 'UTF-8'));
        $xml[] = sprintf('    <link>%s</link>', htmlspecialchars($link, ENT_XML1, 'UTF-8'));
        $xml[] = sprintf('    <description>%s</description>', htmlspecialchars($description, ENT_XML1, 'UTF-8'));
        $xml[] = sprintf('    <language>%s</language>', htmlspecialchars($language, ENT_XML1, 'UTF-8'));

        foreach ($items as $item) {
            $xml[] = '    <item>';
            if (isset($item['title'])) {
                $xml[] = sprintf('      <title>%s</title>', htmlspecialchars($item['title'], ENT_XML1, 'UTF-8'));
            }
            if (isset($item['link'])) {
                $xml[] = sprintf('      <link>%s</link>', htmlspecialchars($item['link'], ENT_XML1, 'UTF-8'));
            }
            if (isset($item['description'])) {
                $xml[] = sprintf('      <description>%s</description>', htmlspecialchars($item['description'], ENT_XML1, 'UTF-8'));
            }
            if (isset($item['pubDate'])) {
                $xml[] = sprintf('      <pubDate>%s</pubDate>', htmlspecialchars($item['pubDate'], ENT_XML1, 'UTF-8'));
            }
            if (isset($item['guid'])) {
                $xml[] = sprintf('      <guid>%s</guid>', htmlspecialchars($item['guid'], ENT_XML1, 'UTF-8'));
            }
            if (isset($item['author'])) {
                $xml[] = sprintf('      <author>%s</author>', htmlspecialchars($item['author'], ENT_XML1, 'UTF-8'));
            }
            $xml[] = '    </item>';
        }

        $xml[] = '  </channel>';
        $xml[] = '</rss>';

        $content = implode("\n", $xml) . "\n";
        $headers['Content-Type'] = 'application/rss+xml; charset=UTF-8';

        parent::__construct($content, $status, $headers);
    }
}
