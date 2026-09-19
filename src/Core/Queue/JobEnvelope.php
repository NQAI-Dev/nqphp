<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

final class JobEnvelope
{
    private string $id;
    private JobInterface $job;
    private int $attempts;
    private int $availableAt;
    private int $createdAt;

    public function __construct(
        string $id,
        JobInterface $job,
        int $attempts = 0,
        int $availableAt = 0,
        int $createdAt = 0
    ) {
        $this->id = $id;
        $this->job = $job;
        $this->attempts = $attempts;
        $this->availableAt = $availableAt > 0 ? $availableAt : time();
        $this->createdAt = $createdAt > 0 ? $createdAt : time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getJob(): JobInterface
    {
        return $this->job;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getAvailableAt(): int
    {
        return $this->availableAt;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }
}
