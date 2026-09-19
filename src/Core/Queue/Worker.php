<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

final class Worker
{
    private QueueInterface $queue;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Process a single available job from the queue.
     *
     * @return bool True if a job was processed, false if queue was empty
     */
    public function processNextJob(): bool
    {
        $envelope = $this->queue->pop();
        if ($envelope === null) {
            return false;
        }

        try {
            $envelope->getJob()->handle();
            $this->queue->ack($envelope->getId());
        } catch (\Throwable $e) {
            $this->queue->fail($envelope, $e);
        }

        return true;
    }

    /**
     * Run worker loop continuously or until stopped.
     */
    public function work(int $sleepSeconds = 2, int $maxJobs = 0): void
    {
        $processed = 0;
        while (true) {
            $hasJob = $this->processNextJob();
            if ($hasJob) {
                $processed++;
                if ($maxJobs > 0 && $processed >= $maxJobs) {
                    break;
                }
            } else {
                sleep($sleepSeconds);
            }
        }
    }
}
