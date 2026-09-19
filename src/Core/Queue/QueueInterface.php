<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

interface QueueInterface
{
    /**
     * Push a job onto the queue.
     *
     * @param JobInterface $job
     * @param int $delay Delay in seconds
     * @return string Job ID
     */
    public function push(JobInterface $job, int $delay = 0): string;

    /**
     * Pop the next available job from the queue.
     *
     * @return JobEnvelope|null
     */
    public function pop(): ?JobEnvelope;

    /**
     * Acknowledge successful processing of a job.
     */
    public function ack(string $jobId): void;

    /**
     * Handle job failure (retry or move to failed).
     */
    public function fail(JobEnvelope $envelope, \Throwable $e): void;

    /**
     * Count pending jobs in the queue.
     */
    public function count(): int;
}
