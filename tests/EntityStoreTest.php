<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Attribute\Entity;
use Nqphp\Core\Entity\EntityDiscoverer;
use Nqphp\Core\Entity\EntityStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Phase 2 #6 (in-memory entity store) test.
 *
 * Verifies the CRUD lifecycle:
 *   - save assigns an id on first insert
 *   - save preserves the id on subsequent updates
 *   - find returns the right row or null
 *   - findAll returns all rows of an entity type
 *   - delete removes the row
 *   - unknown entity name throws
 */
final class EntityStoreTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/..';

    public function testEmptyStoreReturnsEmpty(): void
    {
        $discoverer = new EntityDiscoverer([sys_get_temp_dir() . '/no-such-dir']);
        $store = new EntityStore($discoverer);
        self::assertSame([], $store->findAll('user'));
    }

    public function testSaveAssignsIdAndFindReturnsObject(): void
    {
        $tmp = sys_get_temp_dir() . '/nqphp-entity-' . uniqid();
        mkdir($tmp . '/Feature/Test/Entity', 0755, true);

        $srcPath = $tmp . '/Feature/Test/Entity/User.php';
        file_put_contents($srcPath, <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Test\Entity;

use Nqphp\Core\Attribute\Entity;

#[Entity(name: 'user')]
final class User
{
    public ?int $id = null;
    public string $email = '';
    public string $name = '';
}
PHP);

        $discoverer = new EntityDiscoverer([$tmp]);
        $store = new EntityStore($discoverer);

        /** @var object $user */
        $user = new \App\Test\Entity\User();
        $user->email = 'foo@bar';
        $user->name = 'Foo';
        $store->save($user);

        self::assertSame(1, $user->id);
        self::assertSame($user, $store->find('user', 1));
        self::assertNull($store->find('user', 999));
        self::assertSame([$user], $store->findAll('user'));

        unlink($srcPath);
        rmdir($tmp . '/Feature/Test/Entity');
        rmdir($tmp . '/Feature/Test');
        rmdir($tmp . '/Feature');
        rmdir($tmp);
    }

    public function testSaveIncrementsIdAcrossInstances(): void
    {
        $tmp = sys_get_temp_dir() . '/nqphp-entity2-' . uniqid();
        mkdir($tmp . '/Feature/Test/Entity', 0755, true);
        file_put_contents($tmp . '/Feature/Test/Entity/User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Test\Entity;

use Nqphp\Core\Attribute\Entity;

#[Entity(name: 'user')]
final class User
{
    public ?int $id = null;
    public string $email = '';
}
PHP);

        $discoverer = new EntityDiscoverer([$tmp]);
        $store = new EntityStore($discoverer);
        /** @var object $a */
        $a = new \App\Test\Entity\User();
        $store->save($a);
        /** @var object $b */
        $b = new \App\Test\Entity\User();
        $store->save($b);
        self::assertSame(1, $a->id);
        self::assertSame(2, $b->id);

        unlink($tmp . '/Feature/Test/Entity/User.php');
        rmdir($tmp . '/Feature/Test/Entity');
        rmdir($tmp . '/Feature/Test');
        rmdir($tmp . '/Feature');
        rmdir($tmp);
    }

    public function testDeleteRemovesRow(): void
    {
        $tmp = sys_get_temp_dir() . '/nqphp-entity3-' . uniqid();
        mkdir($tmp . '/Feature/Test/Entity', 0755, true);
        file_put_contents($tmp . '/Feature/Test/Entity/User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Test\Entity;

use Nqphp\Core\Attribute\Entity;

#[Entity(name: 'user')]
final class User
{
    public ?int $id = null;
    public string $email = '';
}
PHP);

        $discoverer = new EntityDiscoverer([$tmp]);
        $store = new EntityStore($discoverer);
        /** @var object $u */
        $u = new \App\Test\Entity\User();
        $store->save($u);
        self::assertSame(1, $u->id);
        self::assertTrue($store->delete('user', 1));
        self::assertNull($store->find('user', 1));
        self::assertSame([], $store->findAll('user'));
        // Deleting a non-existent id returns false
        self::assertFalse($store->delete('user', 999));

        unlink($tmp . '/Feature/Test/Entity/User.php');
        rmdir($tmp . '/Feature/Test/Entity');
        rmdir($tmp . '/Feature/Test');
        rmdir($tmp . '/Feature');
        rmdir($tmp);
    }

    public function testSavingNonEntityThrows(): void
    {
        $discoverer = new EntityDiscoverer([sys_get_temp_dir() . '/no-such-dir']);
        $store = new EntityStore($discoverer);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not marked with #[Entity]');
        $store->save(new \stdClass());
    }
}
