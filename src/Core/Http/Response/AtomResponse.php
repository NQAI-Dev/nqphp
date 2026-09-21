<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response representing an Atom 1.0 syndication feed (application/atom+xml).
 */
class AtomResponse extends Response
{
    /**
     * @param string $id
     * @param string $title
     * @param string $updated ISO-8601 formatted timestamp
     * @param string $link
     * @param list<array{id: string, title: string, updated: string, summary?: string, link?: string, author?: string}> $entries
     * @param int $status
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        string $id,
        string $title,
        string $updated,
        string $link = '',
        array $entries = [],
        int $status = 200,
        array $headers = []
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('Atom feed id cannot be empty.');
        }
        if (trim($title) === '') {
            throw new InvalidArgumentException('Atom feed title cannot be empty.');
        }
        if (trim($updated) === '') {
            throw new InvalidArgumentException('Atom feed updated timestamp cannot be empty.');
        }

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<feed xmlns="http://www.w3.org/2005/Atom">';
        $xml[] = sprintf('  <id>%s</id>', htmlspecialchars($id, ENT_XML1, 'UTF-8'));
        $xml[] = sprintf('  <title>%s</title>', htmlspecialchars($title, ENT_XML1, 'UTF-8'));
        $xml[] = sprintf('  <updated>%s</updated>', htmlspecialchars($updated, ENT_XML1, 'UTF-8'));

        if ($link !== '') {
            $xml[] = sprintf('  <link href="%s" rel="self"/>', htmlspecialchars($link, ENT_XML1, 'UTF-8'));
        }

        foreach ($entries as $entry) {
            $xml[] = '  <entry>';
            $xml[] = sprintf('    <id>%s</id>', htmlspecialchars($entry['id'] ?? '', ENT_XML1, 'UTF-8'));
            $xml[] = sprintf('    <title>%s</title>', htmlspecialchars($entry['title'] ?? '', ENT_XML1, 'UTF-8'));
            $xml[] = sprintf('    <updated>%s</updated>', htmlspecialchars($entry['updated'] ?? '', ENT_XML1, 'UTF-8'));

            if (!empty($entry['link'])) {
                $xml[] = sprintf('    <link href="%s"/>', htmlspecialchars($entry['link'], ENT_XML1, 'UTF-8'));
            }
            if (!empty($entry['summary'])) {
                $xml[] = sprintf('    <summary>%s</summary>', htmlspecialchars($entry['summary'], ENT_XML1, 'UTF-8'));
            }
            if (!empty($entry['author'])) {
                $xml[] = '    <author>';
                $xml[] = sprintf('      <name>%s</name>', htmlspecialchars($entry['author'], ENT_XML1, 'UTF-8'));
                $xml[] = '    </author>';
            }
            $xml[] = '  </entry>';
        }

        $xml[] = '</feed>';

        $content = implode("\n", $xml) . "\n";
        $headers['Content-Type'] = 'application/atom+xml; charset=UTF-8';

        parent::__construct($content, $status, $headers);
    }
}
