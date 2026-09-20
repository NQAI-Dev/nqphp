<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Middleware;

use Nqphp\Core\Middleware\SessionMiddleware;
use Nqphp\Core\Session\ArraySession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionMiddlewareTest extends TestCase
{
    public function testStartsSessionAndAttachesToRequest(): void
    {
        $session = new ArraySession();
        $middleware = new SessionMiddleware($session);

        $this->assertFalse($session->isStarted());

        $request = Request::create('/dashboard', 'GET');

        $response = $middleware->process($request, function (Request $req) use ($session) {
            $this->assertTrue($session->isStarted());
            $this->assertSame($session, $req->attributes->get(SessionMiddleware::ATTRIBUTE_SESSION));
            return new Response('Dashboard content', Response::HTTP_OK);
        });

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Dashboard content', $response->getContent());
    }

    public function testDoesNotRestartAlreadyStartedSession(): void
    {
        $session = new ArraySession();
        $session->start();
        $session->set('user_id', 123);

        $middleware = new SessionMiddleware($session);
        $request = Request::create('/profile', 'GET');

        $called = false;
        $middleware->process($request, function (Request $req) use (&$called, $session) {
            $called = true;
            $this->assertSame(123, $req->attributes->get(SessionMiddleware::ATTRIBUTE_SESSION)->get('user_id'));
            return new Response('OK');
        });

        $this->assertTrue($called);
        $this->assertTrue($session->isStarted());
    }
}
