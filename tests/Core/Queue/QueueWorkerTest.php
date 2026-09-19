<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Queue;

use Nqphp\Core\Queue\AbstractJob;
use Nqphp\Core\Queue\JobEnvelope;
use Nqphp\Core\Queue\JobInterface;
use Nqphp\Core\Queue\QueueInterface;
use Nqphp\Core\Queue\Worker;
use PHPUnit\Framework\TestCase;

// ── In-memory queue for testing ──────────────────────────────────────────────

final class InMemoryQueue implements QueueInterface
{
    /** @var list<JobEnvelope> */
    private array $pending = [];

    /** @var list<JobEnvelope> */
    public array $failed = [];

    /** @var list<string> */
    public array $acked = [];

    private int $idCounter = 0;

    public function push(JobInterface $job, int $delay = 0): string
    {
        $id = 'job-' . (++$this->idCounter);
        $this->pending[] = new JobEnvelope($id, $job, 0, time() + $delay);
        return $id;
    }

    public function pop(): ?JobEnvelope
    {
        $now = time();
        foreach ($this->pending as $i => $envelope) {
            if ($envelope->getAvailableAt() <= $now) {
                unset($this->pending[$i]);
                $this->pending = array_values($this->pending);
                return $envelope;
            }
        }
        return null;
    }

    public function ack(string $jobId): void
    {
        $this->acked[] = $jobId;
    }

    public function fail(JobEnvelope $envelope, \Throwable $e): void
    {
        $this->failed[] = $envelope;
    }

    public function count(): int
    {
        return count($this->pending);
    }
}

// ── Test job fixtures ─────────────────────────────────────────────────────────

final class SuccessJob extends AbstractJob
{
    public bool $executed = false;

    public function handle(): void
    {
        $this->executed = true;
    }
}

final class FailingJob extends AbstractJob
{
    public function handle(): void
    {
        throw new \RuntimeException('Job failed intentionally.');
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────────

class QueueWorkerTest extends TestCase
{
    public function testJobEnvelopeHoldsCorrectData(): void
    {
        $job = new SuccessJob();
        $envelope = new JobEnvelope('test-id', $job, 2, time() + 10, time());

        $this->assertSame('test-id', $envelope->getId());
        $this->assertSame($job, $envelope->getJob());
        $this->assertSame(2, $envelope->getAttempts());
        $this->assertGreaterThan(0, $envelope->getAvailableAt());
        $this->assertGreaterThan(0, $envelope->getCreatedAt());
    }

    public function testAbstractJobDefaultValues(): void
    {
        $job = new SuccessJob();

        $this->assertSame(3, $job->getMaxTries());
        $this->assertSame(60, $job->getRetryDelay());
    }

    public function testQueuePushAndPop(): void
    {
        $queue = new InMemoryQueue();
        $job = new SuccessJob();

        $id = $queue->push($job);
        $this->assertSame(1, $queue->count());

        $envelope = $queue->pop();
        $this->assertNotNull($envelope);
        $this->assertSame($id, $envelope->getId());
        $this->assertSame(0, $queue->count());
    }

    public function testQueuePopReturnsNullWhenEmpty(): void
    {
        $queue = new InMemoryQueue();
        $this->assertNull($queue->pop());
    }

    public function testQueueDelayedJobNotAvailableImmediately(): void
    {
        $queue = new InMemoryQueue();
        $queue->push(new SuccessJob(), 9999);

        $this->assertSame(1, $queue->count());
        $this->assertNull($queue->pop());
    }

    public function testWorkerProcessesSuccessfulJob(): void
    {
        $queue = new InMemoryQueue();
        $job = new SuccessJob();
        $id = $queue->push($job);

        $worker = new Worker($queue);
        $result = $worker->processNextJob();

        $this->assertTrue($result);
        $this->assertTrue($job->executed);
        $this->assertContains($id, $queue->acked);
        $this->assertCount(0, $queue->failed);
    }

    public function testWorkerHandlesFailingJob(): void
    {
        $queue = new InMemoryQueue();
        $queue->push(new FailingJob());

        $worker = new Worker($queue);
        $result = $worker->processNextJob();

        $this->assertTrue($result);
        $this->assertCount(0, $queue->acked);
        $this->assertCount(1, $queue->failed);
    }

    public function testWorkerReturnsFalseWhenQueueEmpty(): void
    {
        $queue = new InMemoryQueue();
        $worker = new Worker($queue);

        $this->assertFalse($worker->processNextJob());
    }

    public function testWorkerProcessesMultipleJobsInOrder(): void
    {
        $queue = new InMemoryQueue();
        $job1 = new SuccessJob();
        $job2 = new SuccessJob();
        $id1 = $queue->push($job1);
        $id2 = $queue->push($job2);

        $worker = new Worker($queue);
        $worker->processNextJob();
        $worker->processNextJob();

        $this->assertTrue($job1->executed);
        $this->assertTrue($job2->executed);
        $this->assertContains($id1, $queue->acked);
        $this->assertContains($id2, $queue->acked);
    }
}
