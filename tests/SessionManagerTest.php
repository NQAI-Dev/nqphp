<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        $this->sessionManager = new SessionManager();
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetAndGet(): void
    {
        $this->sessionManager->set('user_id', 42);
        $this->assertEquals(42, $this->sessionManager->get('user_id'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDefault(): void
    {
        $this->assertNull($this->sessionManager->get('missing'));
        $this->assertEquals('default', $this->sessionManager->get('missing', 'default'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemove(): void
    {
        $this->sessionManager->set('temp_key', 'value');
        $this->sessionManager->remove('temp_key');

        $this->assertNull($this->sessionManager->get('temp_key'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testClear(): void
    {
        $this->sessionManager->set('key1', 'val1');
        $this->sessionManager->set('key2', 'val2');
        $this->sessionManager->clear();

        $this->assertNull($this->sessionManager->get('key1'));
        $this->assertNull($this->sessionManager->get('key2'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroy(): void
    {
        $this->sessionManager->set('key1', 'val1');
        $this->sessionManager->destroy();

        $this->assertFalse(isset($_SESSION['key1']));
    }

    /**
     * @runInSeparateProcess
     */
    public function testFlashMessages(): void
    {
        $this->assertFalse($this->sessionManager->hasFlash('info'));
        $this->assertSame([], $this->sessionManager->getFlash('info'));

        $this->sessionManager->addFlash('info', 'First message');
        $this->sessionManager->addFlash('info', 'Second message');
        $this->sessionManager->addFlash('error', 'Something went wrong');

        $this->assertTrue($this->sessionManager->hasFlash('info'));
        $this->assertTrue($this->sessionManager->hasFlash('error'));
        $this->assertFalse($this->sessionManager->hasFlash('warning'));

        $infoFlashes = $this->sessionManager->getFlash('info');
        $this->assertSame(['First message', 'Second message'], $infoFlashes);

        // Flash messages should be cleared after getFlash()
        $this->assertFalse($this->sessionManager->hasFlash('info'));
        $this->assertSame([], $this->sessionManager->getFlash('info'));

        // 'error' flash is still intact
        $this->assertTrue($this->sessionManager->hasFlash('error'));
        $this->assertSame(['Something went wrong'], $this->sessionManager->getFlash('error'));
        $this->assertFalse($this->sessionManager->hasFlash('error'));
    }
}
