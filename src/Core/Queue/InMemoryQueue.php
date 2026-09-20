<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

use Throwable;

/**
 * In-memory FIFO queue implementation for unit tests, CLI testing, and local development without a database.
 */
class InMemoryQueue implements QueueInterface
{
    /** @var array<string, JobEnvelope> */
    private array $jobs = [];

    /** @var array<string, array{envelope: JobEnvelope, error: Throwable}> */
    private array $failedJobs = [];

    /** @var list<string> */
    private array $acknowledgedJobIds = [];

    private int $sequence = 0;

    public function push(JobInterface $job, int $delay = 0): string
    {
        $this->sequence++;
        $id = 'mem_job_' . $this->sequence;

        $envelope = new JobEnvelope(
            id: $id,
            job: $job,
            attempts: 0,
            availableAt: time() + $delay
        );

        $this->jobs[$id] = $envelope;

        return $id;
    }

    public function pop(): ?JobEnvelope
    {
        $now = time();

        foreach ($this->jobs as $id => $envelope) {
            if ($envelope->getAvailableAt() <= $now) {
                unset($this->jobs[$id]);

                return new JobEnvelope(
                    id: $envelope->getId(),
                    job: $envelope->getJob(),
                    attempts: $envelope->getAttempts() + 1,
                    availableAt: $envelope->getAvailableAt()
                );
            }
        }

        return null;
    }

    public function ack(string $jobId): void
    {
        $this->acknowledgedJobIds[] = $jobId;
        unset($this->jobs[$jobId]);
    }

    public function fail(JobEnvelope $envelope, Throwable $e): void
    {
        $this->failedJobs[$envelope->getId()] = [
            'envelope' => $envelope,
            'error' => $e,
        ];
        unset($this->jobs[$envelope->getId()]);
    }

    public function count(): int
    {
        return count($this->jobs);
    }

    /**
     * @return array<string, array{envelope: JobEnvelope, error: Throwable}>
     */
    public function getFailedJobs(): array
    {
        return $this->failedJobs;
    }

    /**
     * @return list<string>
     */
    public function getAcknowledgedJobIds(): array
    {
        return $this->acknowledgedJobIds;
    }

    public function clear(): void
    {
        $this->jobs = [];
        $this->failedJobs = [];
        $this->acknowledgedJobIds = [];
        $this->sequence = 0;
    }
}
