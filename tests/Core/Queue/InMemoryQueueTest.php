<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Queue;

use Nqphp\Core\Queue\AbstractJob;
use Nqphp\Core\Queue\InMemoryQueue;
use Nqphp\Core\Queue\JobEnvelope;
use Nqphp\Core\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SampleQueueJob extends AbstractJob
{
    public bool $handled = false;

    public function handle(): void
    {
        $this->handled = true;
    }
}

class InMemoryQueueTest extends TestCase
{
    private InMemoryQueue $queue;

    protected function setUp(): void
    {
        $this->queue = new InMemoryQueue();
    }

    public function testImplementsQueueInterface(): void
    {
        $this->assertInstanceOf(QueueInterface::class, $this->queue);
        $this->assertSame(0, $this->queue->count());
    }

    public function testPushAndPopJob(): void
    {
        $job = new SampleQueueJob();
        $id = $this->queue->push($job);

        $this->assertSame(1, $this->queue->count());
        $this->assertStringStartsWith('mem_job_', $id);

        $envelope = $this->queue->pop();
        $this->assertInstanceOf(JobEnvelope::class, $envelope);
        $this->assertSame($id, $envelope->getId());
        $this->assertSame(1, $envelope->getAttempts());
        $this->assertSame($job, $envelope->getJob());

        $this->assertSame(0, $this->queue->count());
    }

    public function testPopReturnsNullWhenQueueIsEmpty(): void
    {
        $this->assertNull($this->queue->pop());
    }

    public function testDelayedJobNotPoppedImmediately(): void
    {
        $job = new SampleQueueJob();
        $this->queue->push($job, 60);

        $this->assertSame(1, $this->queue->count());
        $this->assertNull($this->queue->pop());
    }

    public function testAckAndFail(): void
    {
        $job1 = new SampleQueueJob();
        $id1 = $this->queue->push($job1);

        $this->queue->ack($id1);
        $this->assertSame([$id1], $this->queue->getAcknowledgedJobIds());
        $this->assertSame(0, $this->queue->count());

        $job2 = new SampleQueueJob();
        $id2 = $this->queue->push($job2);
        $envelope = $this->queue->pop();

        $error = new RuntimeException('Job failed');
        $this->queue->fail($envelope, $error);

        $failed = $this->queue->getFailedJobs();
        $this->assertArrayHasKey($id2, $failed);
        $this->assertSame($error, $failed[$id2]['error']);
        $this->assertSame($envelope, $failed[$id2]['envelope']);
    }

    public function testClearResetsQueue(): void
    {
        $this->queue->push(new SampleQueueJob());
        $this->assertSame(1, $this->queue->count());

        $this->queue->clear();
        $this->assertSame(0, $this->queue->count());
        $this->assertNull($this->queue->pop());
    }
}
