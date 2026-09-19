<?php

declare(strict_types=1);

namespace Nqphp\Core\Filesystem;

use InvalidArgumentException;

final class StorageManager
{
    /**
     * @var array<string, StorageInterface>
     */
    private array $disks = [];

    private string $defaultDisk;

    public function __construct(string $defaultDisk = 'local')
    {
        $this->defaultDisk = $defaultDisk;
    }

    public function mount(string $name, StorageInterface $storage): self
    {
        $this->disks[$name] = $storage;
        return $this;
    }

    public function disk(?string $name = null): StorageInterface
    {
        $target = $name ?? $this->defaultDisk;
        if (!isset($this->disks[$target])) {
            throw new InvalidArgumentException("Disk [{$target}] is not mounted.");
        }
        return $this->disks[$target];
    }
}
