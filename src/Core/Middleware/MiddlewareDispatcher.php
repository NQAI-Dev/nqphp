<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareDispatcher
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    /**
     * @var callable
     */
    private $coreHandler;

    public function __construct(callable $coreHandler)
    {
        $this->coreHandler = $coreHandler;
    }

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $index = 0;

        $next = function (Request $req) use (&$index, &$next): Response {
            if ($index < count($this->middlewares)) {
                $middleware = $this->middlewares[$index];
                $index++;
                return $middleware->process($req, $next);
            }

            // Core execution (controller logic) runs when no more middlewares
            $handler = $this->coreHandler;
            return $handler($req);
        };

        return $next($request);
    }
}
