<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Response;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP response for serving image binary payloads with automated or explicit MIME type detection.
 */
class ImageResponse extends Response
{
    private const MIME_TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
    ];

    /**
     * @param string $content Raw image binary or SVG content
     * @param string $format Format/extension ('png', 'jpg', 'svg', etc.) or full mime type
     * @param int $status
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        string $content,
        string $format = 'png',
        int $status = 200,
        array $headers = []
    ) {
        $mime = $this->resolveMimeType($format);

        $defaultHeaders = [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($content),
        ];

        $headers = array_merge($defaultHeaders, $headers);

        parent::__construct($content, $status, $headers);
    }

    private function resolveMimeType(string $format): string
    {
        $normalized = strtolower(trim($format));

        if (str_starts_with($normalized, 'image/')) {
            return $normalized;
        }

        if (isset(self::MIME_TYPES[$normalized])) {
            return self::MIME_TYPES[$normalized];
        }

        throw new InvalidArgumentException("Неподдерживаемый формат изображения: '{$format}'.");
    }
}
