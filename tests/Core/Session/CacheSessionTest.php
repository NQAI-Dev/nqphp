<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Session;

use Nqphp\Core\Cache\ArrayCache;
use Nqphp\Core\Session\CacheSession;
use Nqphp\Core\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

class CacheSessionTest extends TestCase
{
    private ArrayCache $cache;
    private CacheSession $session;

    protected function setUp(): void
    {
        $this->cache = new ArrayCache();
        $this->session = new CacheSession($this->cache, 'sess_12345', 3600);
    }

    public function testImplementsSessionInterface(): void
    {
        $this->assertInstanceOf(SessionInterface::class, $this->session);
    }

    public function testSetGetHasAndRemove(): void
    {
        $this->assertFalse($this->session->has('user_id'));
        $this->assertNull($this->session->get('user_id'));
        $this->assertSame('guest', $this->session->get('user_id', 'guest'));

        $this->session->set('user_id', 42);
        $this->assertTrue($this->session->has('user_id'));
        $this->assertSame(42, $this->session->get('user_id'));

        $this->session->remove('user_id');
        $this->assertFalse($this->session->has('user_id'));
    }

    public function testPersistenceAcrossInstances(): void
    {
        $this->session->set('role', 'admin');

        $session2 = new CacheSession($this->cache, 'sess_12345', 3600);
        $this->assertTrue($session2->has('role'));
        $this->assertSame('admin', $session2->get('role'));
    }

    public function testFlashMessages(): void
    {
        $this->assertFalse($this->session->hasFlash('success'));
        $this->assertSame([], $this->session->getFlash('success'));

        $this->session->addFlash('success', 'Profile updated.');
        $this->assertTrue($this->session->hasFlash('success'));

        $flashes = $this->session->getFlash('success');
        $this->assertSame(['Profile updated.'], $flashes);

        // Flash is consumed and cleared
        $this->assertFalse($this->session->hasFlash('success'));
        $this->assertSame([], $this->session->getFlash('success'));
    }

    public function testClearAndDestroy(): void
    {
        $this->session->set('foo', 'bar');
        $this->session->clear();
        $this->assertFalse($this->session->has('foo'));

        $this->session->set('foo', 'bar');
        $this->session->destroy();
        $this->assertFalse($this->session->isStarted());
        $this->assertFalse($this->session->has('foo'));
    }

    public function testRegenerate(): void
    {
        $this->session->set('token', 'xyz');
        $oldId = $this->session->getId();

        $this->session->regenerate(true);
        $newId = $this->session->getId();

        $this->assertNotSame($oldId, $newId);
        $this->assertFalse($this->cache->has('session:' . $oldId));
        $this->assertTrue($this->cache->has('session:' . $newId));
        $this->assertSame('xyz', $this->session->get('token'));
    }
}
