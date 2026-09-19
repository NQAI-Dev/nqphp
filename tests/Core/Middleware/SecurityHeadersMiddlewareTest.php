<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Middleware;

use Nqphp\Core\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testAddsDefaultSecurityHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/');

        $response = $middleware->process($request, function (Request $req) {
            return new Response('OK');
        });

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
    }

    public function testDoesNotOverwriteExistingHeader(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/');

        $response = $middleware->process($request, function (Request $req) {
            $res = new Response('Custom');
            $res->headers->set('X-Frame-Options', 'DENY');
            return $res;
        });

        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testCustomHeadersCanBeConfigured(): void
    {
        $middleware = new SecurityHeadersMiddleware([
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'X-Frame-Options' => 'DENY',
        ]);
        $request = Request::create('/');

        $response = $middleware->process($request, function (Request $req) {
            return new Response('Configured');
        });

        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }
}
