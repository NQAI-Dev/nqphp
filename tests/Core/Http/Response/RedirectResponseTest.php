<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http\Response;

use Nqphp\Core\Http\Response\RedirectResponse;
use PHPUnit\Framework\TestCase;

class RedirectResponseTest extends TestCase
{
    public function testDefaultRedirectIs302(): void
    {
        $response = new RedirectResponse('/login');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getTargetUrl());
    }

    public function testPermanentRedirect(): void
    {
        $response = RedirectResponse::permanent('/new-url', ['X-Custom' => 'val']);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/new-url', $response->getTargetUrl());
        $this->assertSame('val', $response->headers->get('X-Custom'));
    }

    public function testTemporaryRedirect(): void
    {
        $response = RedirectResponse::temporary('/temp-target');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/temp-target', $response->getTargetUrl());
    }

    public function testSeeOtherRedirect(): void
    {
        $response = RedirectResponse::seeOther('/items');
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/items', $response->getTargetUrl());
    }
}
