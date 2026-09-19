<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security;

use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Security\AuthenticationMiddleware;
use Nqphp\Core\Security\SecurityContext;
use Nqphp\Core\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationMiddlewareTest extends TestCase
{
    public function testAllowsAuthenticatedUser(): void
    {
        $security = new SecurityContext();
        $security->setUser(new User('john_doe', ['ROLE_USER']));

        $middleware = new AuthenticationMiddleware($security);
        $request = Request::create('/dashboard');

        $response = $middleware->process($request, fn (Request $req) => new Response('OK', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', (string) $response->getContent());
    }

    public function testThrows401WhenUnauthenticated(): void
    {
        $security = new SecurityContext();
        $middleware = new AuthenticationMiddleware($security);
        $request = Request::create('/dashboard');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Authentication required.');

        $middleware->process($request, fn (Request $req) => new Response('OK', 200));
    }

    public function testRedirectsWhenUnauthenticatedAndRedirectUrlSet(): void
    {
        $security = new SecurityContext();
        $middleware = new AuthenticationMiddleware($security, redirectUrl: '/login');
        $request = Request::create('/dashboard');

        $response = $middleware->process($request, fn (Request $req) => new Response('OK', 200));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->headers->get('Location'));
    }

    public function testThrows403WhenRoleMissing(): void
    {
        $security = new SecurityContext();
        $security->setUser(new User('alice', ['ROLE_USER']));

        $middleware = new AuthenticationMiddleware($security, requiredRole: 'ROLE_ADMIN');
        $request = Request::create('/admin');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Access denied.');

        $middleware->process($request, fn (Request $req) => new Response('OK', 200));
    }

    public function testAllowsWhenRoleMatches(): void
    {
        $security = new SecurityContext();
        $security->setUser(new User('bob', ['ROLE_ADMIN', 'ROLE_USER']));

        $middleware = new AuthenticationMiddleware($security, requiredRole: 'ROLE_ADMIN');
        $request = Request::create('/admin');

        $response = $middleware->process($request, fn (Request $req) => new Response('Admin OK', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Admin OK', (string) $response->getContent());
    }
}
