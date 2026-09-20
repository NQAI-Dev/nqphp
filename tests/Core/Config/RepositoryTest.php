<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Config;

use Nqphp\Core\Config\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function testGetAndHasWithFlatKey(): void
    {
        $repo = new Repository(['app_name' => 'nqphp', 'debug' => true]);

        $this->assertTrue($repo->has('app_name'));
        $this->assertSame('nqphp', $repo->get('app_name'));
        $this->assertTrue($repo->has('debug'));
        $this->assertTrue($repo->get('debug'));
        $this->assertFalse($repo->has('unknown'));
        $this->assertSame('default', $repo->get('unknown', 'default'));
    }

    public function testGetAndHasWithNestedDotNotation(): void
    {
        $repo = new Repository([
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'database' => ':memory:',
                    ],
                ],
            ],
        ]);

        $this->assertTrue($repo->has('database.default'));
        $this->assertSame('sqlite', $repo->get('database.default'));
        $this->assertTrue($repo->has('database.connections.sqlite.database'));
        $this->assertSame(':memory:', $repo->get('database.connections.sqlite.database'));

        $this->assertFalse($repo->has('database.connections.mysql.host'));
        $this->assertNull($repo->get('database.connections.mysql.host'));
        $this->assertSame('localhost', $repo->get('database.connections.mysql.host', 'localhost'));
    }

    public function testSetCreatesNestedKeys(): void
    {
        $repo = new Repository();
        $repo->set('app.timezone', 'UTC');
        $repo->set('services.mail.smtp.port', 587);

        $this->assertTrue($repo->has('app.timezone'));
        $this->assertSame('UTC', $repo->get('app.timezone'));
        $this->assertSame(587, $repo->get('services.mail.smtp.port'));

        $expected = [
            'app' => ['timezone' => 'UTC'],
            'services' => [
                'mail' => [
                    'smtp' => [
                        'port' => 587,
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $repo->all());
    }

    public function testSetOverwritesExistingKey(): void
    {
        $repo = new Repository(['app' => ['name' => 'old']]);
        $repo->set('app.name', 'new');

        $this->assertSame('new', $repo->get('app.name'));
    }
}
