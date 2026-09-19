<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Session;

use Nqphp\Core\Session\ArraySession;
use PHPUnit\Framework\TestCase;

class ArraySessionTest extends TestCase
{
    private ArraySession $session;

    protected function setUp(): void
    {
        $this->session = new ArraySession();
    }

    public function testStartAndIsStarted(): void
    {
        $this->assertFalse($this->session->isStarted());
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
    }

    public function testSetGetHasAndRemove(): void
    {
        $this->session->set('user_id', 123);
        $this->assertTrue($this->session->has('user_id'));
        $this->assertSame(123, $this->session->get('user_id'));

        $this->session->remove('user_id');
        $this->assertFalse($this->session->has('user_id'));
        $this->assertNull($this->session->get('user_id'));
        $this->assertSame('fallback', $this->session->get('user_id', 'fallback'));
    }

    public function testClear(): void
    {
        $this->session->set('a', 1);
        $this->session->set('b', 2);
        $this->assertSame(['a' => 1, 'b' => 2], $this->session->all());

        $this->session->clear();
        $this->assertSame([], $this->session->all());
    }

    public function testDestroy(): void
    {
        $this->session->start();
        $this->session->set('auth', true);
        $this->session->addFlash('info', 'Logged in');

        $this->session->destroy();
        $this->assertFalse($this->session->isStarted());
        $this->assertSame([], $this->session->all());
        $this->assertSame([], $this->session->getFlash('info'));
    }

    public function testFlashMessages(): void
    {
        $this->assertFalse($this->session->hasFlash('success'));
        $this->assertSame([], $this->session->getFlash('success'));

        $this->session->addFlash('success', 'Profile updated');
        $this->session->addFlash('success', 'Email sent');

        $this->assertTrue($this->session->hasFlash('success'));
        $this->assertSame(['Profile updated', 'Email sent'], $this->session->getFlash('success'));
        $this->assertFalse($this->session->hasFlash('success'));
    }
}
