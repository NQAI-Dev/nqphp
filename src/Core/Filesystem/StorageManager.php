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

    public function hasDisk(string $name): bool
    {
        return isset($this->disks[$name]);
    }

    public function unmount(string $name): self
    {
        unset($this->disks[$name]);
        return $this;
    }

    public function getDefaultDisk(): string
    {
        return $this->defaultDisk;
    }

    public function setDefaultDisk(string $defaultDisk): self
    {
        $this->defaultDisk = $defaultDisk;
        return $this;
    }

    /**
     * @return list<string>
     */
    public function getDisks(): array
    {
        return array_keys($this->disks);
    }
}
