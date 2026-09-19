<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Queue\AbstractJob;
use Nqphp\Core\Queue\DatabaseQueue;
use Nqphp\Core\Queue\Worker;
use PDO;
use PHPUnit\Framework\TestCase;

class TestJob extends AbstractJob
{
    public static bool $handled = false;

    public function handle(): void
    {
        self::$handled = true;
    }
}

class FailingJob extends AbstractJob
{
    public static int $attempts = 0;

    public function __construct()
    {
        $this->maxTries = 2;
        $this->retryDelay = 0; // immediate retry for test
    }

    public function handle(): void
    {
        self::$attempts++;
        throw new \RuntimeException('Job failure');
    }
}

final class QueueTest extends TestCase
{
    private PDO $pdo;
    private DatabaseQueue $queue;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->queue = new DatabaseQueue($this->pdo, '_test_jobs');
        TestJob::$handled = false;
        FailingJob::$attempts = 0;
    }

    public function testPushAndProcessJob(): void
    {
        $job = new TestJob();
        $this->queue->push($job);
        $this->assertSame(1, $this->queue->count());

        $worker = new Worker($this->queue);
        $processed = $worker->processNextJob();

        $this->assertTrue($processed);
        $this->assertTrue(TestJob::$handled);
        $this->assertSame(0, $this->queue->count());
    }

    public function testFailingJobRetryAndExhaustion(): void
    {
        $job = new FailingJob();
        $this->queue->push($job);

        $worker = new Worker($this->queue);

        // Attempt 1 -> fails, retries
        $worker->processNextJob();
        $this->assertSame(1, FailingJob::$attempts);
        $this->assertSame(1, $this->queue->count());

        // Attempt 2 -> fails, maxTries reached, dropped
        $worker->processNextJob();
        $this->assertSame(2, FailingJob::$attempts);
        $this->assertSame(0, $this->queue->count());
    }
}
