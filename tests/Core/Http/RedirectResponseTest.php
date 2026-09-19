<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Http;

use Nqphp\Core\Http\RedirectResponse;
use Nqphp\Core\Session\ArraySession;
use PHPUnit\Framework\TestCase;

class RedirectResponseTest extends TestCase
{
    public function testRedirectTargetUrlAndStatus(): void
    {
        $response = new RedirectResponse('/dashboard', 301);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/dashboard', $response->getTargetUrl());
    }

    public function testWithFlashSetsFlashMessageInSession(): void
    {
        $session = new ArraySession();
        $response = (new RedirectResponse('/login'))
            ->withSession($session)
            ->withFlash('info', 'Please sign in.');

        $this->assertTrue($session->hasFlash('info'));
        $this->assertSame(['Please sign in.'], $session->getFlash('info'));
    }

    public function testWithErrorAndWithSuccessShortcuts(): void
    {
        $session = new ArraySession();
        $response = (new RedirectResponse('/profile'))
            ->withSession($session)
            ->withError('Update failed.')
            ->withSuccess('Profile loaded.');

        $this->assertTrue($session->hasFlash('error'));
        $this->assertTrue($session->hasFlash('success'));
        $this->assertSame(['Update failed.'], $session->getFlash('error'));
        $this->assertSame(['Profile loaded.'], $session->getFlash('success'));
    }

    public function testWithFlashWithoutSessionIsNoop(): void
    {
        $response = (new RedirectResponse('/home'))
            ->withError('Something went wrong');

        $this->assertSame('/home', $response->getTargetUrl());
    }
}
