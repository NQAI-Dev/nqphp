<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\XmlResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class XmlResponseTest extends TestCase
{
    public function testDefaultContentTypeAndStatus(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><item>test</item></root>';
        $response = new XmlResponse($xml);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($xml, $response->getContent());
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testCustomStatusAndHeaders(): void
    {
        $xml = '<error><code>400</code><message>Bad Request</message></error>';
        $response = new XmlResponse($xml, 400, [
            'X-Custom' => 'Bar',
        ]);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame($xml, $response->getContent());
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('Bar', $response->headers->get('X-Custom'));
    }

    public function testPreservesExplicitContentType(): void
    {
        $xml = '<feed xmlns="http://www.w3.org/2005/Atom"></feed>';
        $response = new XmlResponse($xml, 200, [
            'Content-Type' => 'application/atom+xml; charset=UTF-8',
        ]);

        $this->assertSame('application/atom+xml; charset=UTF-8', $response->headers->get('Content-Type'));
    }
}
