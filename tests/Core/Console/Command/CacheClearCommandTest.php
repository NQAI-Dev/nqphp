<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Console\Command;

use Nqphp\Core\Cache\Cache;
use Nqphp\Core\Console\Command\CacheClearCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CacheClearCommandTest extends TestCase
{
    private Cache $cache;
    private CacheClearCommand $command;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->cache = new Cache();
        $this->command = new CacheClearCommand($this->cache);
        $this->tester = new CommandTester($this->command);
    }

    public function testClearDefaultCacheWhenNoStoresCreated(): void
    {
        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('Default cache cleared successfully.', $this->tester->getDisplay());
    }

    public function testClearSpecificStore(): void
    {
        $weatherStore = $this->cache->store('weather');
        $weatherStore->set('city', 'Kyiv');
        $defaultStore = $this->cache->store('default');
        $defaultStore->set('session', 'active');

        $this->tester->execute(['--store' => 'weather']);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('Cache store [weather] cleared successfully.', $this->tester->getDisplay());
        $this->assertNull($weatherStore->get('city'));
        $this->assertSame('active', $defaultStore->get('session'));
    }

    public function testClearAllActiveStores(): void
    {
        $store1 = $this->cache->store('users');
        $store1->set('u1', 'John');
        $store2 = $this->cache->store('posts');
        $store2->set('p1', 'Hello');

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('Clearing cache store [users]...', $this->tester->getDisplay());
        $this->assertStringContainsString('Clearing cache store [posts]...', $this->tester->getDisplay());
        $this->assertStringContainsString('All active cache stores cleared successfully.', $this->tester->getDisplay());

        $this->assertNull($store1->get('u1'));
        $this->assertNull($store2->get('p1'));
    }
}
