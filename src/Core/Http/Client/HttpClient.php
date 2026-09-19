<?php

declare(strict_types=1);

namespace Nqphp\Core\Http\Client;

use Symfony\Component\HttpFoundation\Response;

final class HttpClient implements HttpClientInterface
{
    /** @var array<string, mixed> */
    private array $defaultOptions;

    /**
     * @param array<string, mixed> $defaultOptions
     */
    public function __construct(array $defaultOptions = [])
    {
        $this->defaultOptions = array_merge([
            'timeout' => 10.0,
            'max_redirects' => 5,
            'headers' => [
                'User-Agent' => 'nqphp-HttpClient/1.0',
                'Accept' => '*/*',
            ],
        ], $defaultOptions);
    }

    public function request(string $method, string $url, array $options = []): Response
    {
        $options = array_replace_recursive($this->defaultOptions, $options);
        $method = strtoupper($method);

        // Process query parameters
        if (!empty($options['query'])) {
            $queryString = http_build_query($options['query']);
            $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
        }

        $headers = $options['headers'] ?? [];

        // Auth shortcuts
        if (!empty($options['bearer_token'])) {
            $headers['Authorization'] = 'Bearer ' . $options['bearer_token'];
        } elseif (!empty($options['basic_auth'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode($options['basic_auth'][0] . ':' . $options['basic_auth'][1]);
        }

        // Body formatting
        $body = '';
        if (isset($options['json'])) {
            $body = json_encode($options['json'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = $headers['Accept'] ?? 'application/json';
        } elseif (isset($options['body'])) {
            $body = (string) $options['body'];
        }

        if ($body !== '' && !isset($headers['Content-Length'])) {
            $headers['Content-Length'] = (string) strlen($body);
        }

        $formattedHeaders = [];
        foreach ($headers as $k => $v) {
            $formattedHeaders[] = is_int($k) ? $v : "{$k}: {$v}";
        }

        $timeout = (float) ($options['timeout'] ?? 10.0);
        $maxRedirects = (int) ($options['max_redirects'] ?? 5);

        $contextOptions = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $formattedHeaders),
                'content' => $body,
                'timeout' => $timeout,
                'ignore_errors' => true,
                'follow_location' => $maxRedirects > 0 ? 1 : 0,
                'max_redirects' => $maxRedirects,
            ],
            'ssl' => [
                'verify_peer' => $options['verify_peer'] ?? true,
                'verify_peer_name' => $options['verify_peer_name'] ?? true,
            ],
        ];

        $context = stream_context_create($contextOptions);

        $level = error_reporting(0);
        $stream = fopen($url, 'r', false, $context);
        error_reporting($level);

        if ($stream === false) {
            $err = error_get_last();
            throw new HttpClientException("HTTP request failed for URL '{$url}': " . ($err['message'] ?? 'Unknown network error'));
        }

        $meta = stream_get_meta_data($stream);
        $responseBody = stream_get_contents($stream);
        fclose($stream);

        $wrapperData = $meta['wrapper_data'] ?? [];
        $rawHeaders = is_array($wrapperData) ? $wrapperData : [];

        $statusCode = 200;
        $responseHeaders = [];

        foreach ($rawHeaders as $headerLine) {
            if (preg_match('#^HTTP/\d\.\d\s+(\d{3})#', $headerLine, $matches)) {
                $statusCode = (int) $matches[1];
                continue;
            }

            $pos = strpos($headerLine, ':');
            if ($pos !== false) {
                $hName = strtolower(trim(substr($headerLine, 0, $pos)));
                $hValue = trim(substr($headerLine, $pos + 1));
                if (isset($responseHeaders[$hName])) {
                    if (!is_array($responseHeaders[$hName])) {
                        $responseHeaders[$hName] = [$responseHeaders[$hName]];
                    }
                    $responseHeaders[$hName][] = $hValue;
                } else {
                    $responseHeaders[$hName] = $hValue;
                }
            }
        }

        return new Response($responseBody ?: '', $statusCode, $responseHeaders);
    }

    public function get(string $url, array $options = []): Response
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $options = []): Response
    {
        return $this->request('POST', $url, $options);
    }

    public function put(string $url, array $options = []): Response
    {
        return $this->request('PUT', $url, $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->request('PATCH', $url, $options);
    }

    public function delete(string $url, array $options = []): Response
    {
        return $this->request('DELETE', $url, $options);
    }
}
