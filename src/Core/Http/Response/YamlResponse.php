<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * HTTP response for serving YAML documents with automated serialization and charset headers.
 */
class YamlResponse extends Response
{
    /**
     * @param mixed $data Data to be serialized into YAML
     * @param int $status HTTP status code (defaults to 200)
     * @param array<string, string|string[]> $headers Additional HTTP headers
     * @param int $inline Inline level for YAML dumper
     * @param int $indent Indentation spaces
     * @param int $flags YAML encode flags
     */
    public function __construct(
        mixed $data = null,
        int $status = 200,
        array $headers = [],
        int $inline = 2,
        int $indent = 4,
        int $flags = 0
    ) {
        $content = $data !== null ? Yaml::dump($data, $inline, $indent, $flags) : "--- {}\n";

        $defaultHeaders = [
            'Content-Type' => 'application/x-yaml; charset=UTF-8',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        parent::__construct($content, $status, $headers);
    }
}
