<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Exception;

use Exception;
use Nqphp\Core\Exception\HttpException;
use Nqphp\Core\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testHttpExceptionPreservesStatusCodeAndMessage(): void
    {
        $prev = new Exception('Root cause');
        $e = new HttpException(403, 'Forbidden action', $prev);

        $this->assertSame(403, $e->getStatusCode());
        $this->assertSame(403, $e->getCode());
        $this->assertSame('Forbidden action', $e->getMessage());
        $this->assertSame($prev, $e->getPrevious());
    }

    public function testNotFoundExceptionDefaults(): void
    {
        $e = new NotFoundException();

        $this->assertSame(404, $e->getStatusCode());
        $this->assertSame(404, $e->getCode());
        $this->assertSame('Not Found', $e->getMessage());
    }

    public function testNotFoundExceptionCustomMessageAndPrevious(): void
    {
        $prev = new Exception('Entity missing');
        $e = new NotFoundException('User #42 was not found', $prev);

        $this->assertSame(404, $e->getStatusCode());
        $this->assertSame('User #42 was not found', $e->getMessage());
        $this->assertSame($prev, $e->getPrevious());
    }
}
