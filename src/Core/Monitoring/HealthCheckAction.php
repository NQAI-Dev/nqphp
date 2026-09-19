<?php

declare(strict_types=1);

namespace Nqphp\Core\Monitoring;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class HealthCheckAction
{
    private HealthCheckRegistry $registry;

    public function __construct(HealthCheckRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function __invoke(): JsonResponse
    {
        $report = $this->registry->runAll();
        $httpStatus = match ($report['status']) {
            'ok' => Response::HTTP_OK,
            'degraded' => Response::HTTP_OK,
            'down' => Response::HTTP_SERVICE_UNAVAILABLE,
        };

        return new JsonResponse($report, $httpStatus);
    }
}
