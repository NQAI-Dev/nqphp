<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Attribute\Controller;
use Nqphp\Core\Attribute\Route;
use Nqphp\Core\Cache\Cache;
use Nqphp\Core\Controller\AbstractController;
use Nqphp\Core\Entity\EntityManager;
use Nqphp\Core\Event\EventDispatcher;
use Nqphp\Core\Kernel\Kernel;
use Nqphp\Core\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Controller]
class DummyCoreServicesController extends AbstractController
{
    #[Route('/test-core-autowire', methods: ['GET'])]
    public function autowireAction(
        EntityManager $em,
        EventDispatcher $dispatcher,
        Cache $cache,
        SessionInterface $session
    ): Response {
        return new JsonResponse([
            'has_em' => $em instanceof EntityManager,
            'has_dispatcher' => $dispatcher instanceof EventDispatcher,
            'has_cache' => $cache instanceof Cache,
            'has_session' => $session instanceof SessionInterface,
            'helper_em' => $this->em() instanceof EntityManager,
        ]);
    }
}

class KernelAutowiringCoreServicesTest extends TestCase
{
    public function testKernelAutowiresCoreServicesIntoAction(): void
    {
        $kernel = new Kernel(__DIR__ . '/fixtures/app');
        $request = Request::create('/test-core-autowire', 'GET');

        // Execute action directly through kernel reflection runner
        $controller = new DummyCoreServicesController($kernel);

        $reflectionMethod = new \ReflectionMethod($controller, 'autowireAction');
        $kernelReflection = new \ReflectionClass($kernel);
        $resolveMethod = $kernelReflection->getMethod('resolveArgs');

        $args = $resolveMethod->invoke($kernel, $reflectionMethod, [], $request);

        /** @var JsonResponse $response */
        $response = $reflectionMethod->invokeArgs($controller, $args);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['has_em']);
        $this->assertTrue($data['has_dispatcher']);
        $this->assertTrue($data['has_cache']);
        $this->assertTrue($data['has_session']);
        $this->assertTrue($data['helper_em']);
    }
}
