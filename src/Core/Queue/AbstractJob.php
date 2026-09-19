<?php

declare(strict_types=1);

namespace Nqphp\Core\Queue;

abstract class AbstractJob implements JobInterface
{
    protected int $maxTries = 3;
    protected int $retryDelay = 60;

    public function getMaxTries(): int
    {
        return $this->maxTries;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
}
