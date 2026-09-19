<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

interface JobInterface
{
    /**
     * Execute the job logic.
     */
    public function handle(): void;

    /**
     * Number of times the job may be attempted before failing.
     */
    public function getMaxTries(): int;

    /**
     * Delay in seconds before retrying a failed job.
     */
    public function getRetryDelay(): int;
}
