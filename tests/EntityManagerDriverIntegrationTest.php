<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Entity\Driver\InMemoryDriver;
use Nqphp\Core\Entity\Driver\SqliteDriver;
use Nqphp\Core\Entity\EntityDiscoverer;
use Nqphp\Core\Entity\EntityManager;
use PHPUnit\Framework\TestCase;

final class EntityManagerDriverIntegrationTest extends TestCase
{
    public function testMappedValuesRoundTripThroughBothDrivers(): void
    {
        $tmp = sys_get_temp_dir() . '/nqphp-driver-integration-' . uniqid();
        $entityDir = $tmp . '/Feature/Driver/Entity';
        mkdir($entityDir, 0755, true);
        $source = $entityDir . '/Record.php';
        file_put_contents($source, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Driver\Entity;

use Nqphp\Core\Attribute\Column;
use Nqphp\Core\Attribute\Entity;
use Nqphp\Core\Attribute\Id;

#[Entity(name: 'driver_record')]
final class Record
{
    #[Id]
    public ?int $id = null;

    #[Column(name: 'display_name')]
    public string $displayName = '';

    #[Column]
    public bool $active = false;

    #[Column]
    public array $metadata = [];

    #[Column]
    public \DateTimeImmutable $createdAt;
}
PHP);
        require_once $source;

        try {
            $drivers = [
                new InMemoryDriver(),
                new SqliteDriver(new \PDO('sqlite::memory:')),
            ];

            foreach ($drivers as $driver) {
                $manager = new EntityManager(new EntityDiscoverer([$tmp]), $driver);
                $record = new \App\Driver\Entity\Record();
                $record->displayName = 'First';
                $record->active = true;
                $record->metadata = ['role' => 'admin'];
                $record->createdAt = new \DateTimeImmutable('2026-09-18T20:30:00+00:00');

                $manager->persist($record);
                $found = $manager->findOneBy(
                    \App\Driver\Entity\Record::class,
                    ['displayName' => 'First'],
                );

                self::assertNotNull($found);
                self::assertSame(1, $found->id);
                self::assertTrue($found->active);
                self::assertSame(['role' => 'admin'], $found->metadata);
                self::assertSame('2026-09-18T20:30:00+00:00', $found->createdAt->format(\DateTimeInterface::ATOM));
            }
        } finally {
            @unlink($source);
            @rmdir($entityDir);
            @rmdir($tmp . '/Feature/Driver');
            @rmdir($tmp . '/Feature');
            @rmdir($tmp);
        }
    }
}
