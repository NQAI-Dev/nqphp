<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Attribute\Assert;
use Nqphp\Core\Attribute\Input;
use Nqphp\Core\Controller\AbstractController;
use Nqphp\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;

/**
 * Validation feature slice: #[Input] DTO hydration from the live
 * request + #[Assert] validation + 422 problem+json error rendering.
 *
 * Verifies the full path:
 *   Request (JSON body / form / query) → RequestData (re-bound by the
 *   kernel per request) → AbstractController::validate() →
 *   ValidationException → ErrorResponseFormatter with the per-field
 *   `errors` map.
 */
#[Input(name: 'widget:create')]
final class CreateWidgetInput
{
    #[Assert('required')]
    #[Assert('email')]
    public string $email = '';

    #[Assert('required')]
    #[Assert('min', options: 3)]
    public string $name = '';
}

final class WidgetValidationController extends AbstractController
{
    public function create(): JsonResponse
    {
        $dto = $this->validate(CreateWidgetInput::class);

        return $this->json(['email' => $dto->email, 'name' => $dto->name]);
    }
}

final class ControllerValidationTest extends TestCase
{
    private const PROJECT_DIR = __DIR__ . '/..';

    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel(self::PROJECT_DIR);
        $this->addRoute($this->kernel, '/_test/widgets', WidgetValidationController::class . '::create');
    }

    public function testValidJsonInputPassesValidation(): void
    {
        $response = $this->kernel->handle($this->jsonRequest([
            'email' => 'ada@example.com',
            'name' => 'Engine',
        ]));

        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame(['email' => 'ada@example.com', 'name' => 'Engine'], $payload);
    }

    public function testInvalidJsonInputRenders422ProblemJsonWithFieldErrors(): void
    {
        $response = $this->kernel->handle($this->jsonRequest([
            'email' => 'not-an-email',
            'name' => 'ab',
        ]));

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('application/problem+json', (string) $response->headers->get('Content-Type'));

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame(422, $payload['status']);
        self::assertSame('Validation failed', $payload['detail']);
        self::assertContains('Invalid email format', $payload['errors']['email']);
        self::assertArrayHasKey('name', $payload['errors']);
    }

    public function testMissingRequiredFieldsAreReported(): void
    {
        $response = $this->kernel->handle($this->jsonRequest([]));

        self::assertSame(422, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('email', $payload['errors']);
        self::assertArrayHasKey('name', $payload['errors']);
    }

    public function testQueryInputIsHydratedFromLiveRequest(): void
    {
        $request = Request::create(
            '/_test/widgets?email=grace@example.com&name=Compiler',
            'POST',
            server: ['HTTP_ACCEPT' => 'application/json'],
        );

        $response = $this->kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('grace@example.com', $payload['email']);
        self::assertSame('Compiler', $payload['name']);
    }

    public function testInputReflectsTheDispatchedRequestNotTheConstructorPlaceholder(): void
    {
        // First request feeds one payload, second request another: DTO
        // hydration must follow the live request (RequestData::bind).
        $first = $this->kernel->handle($this->jsonRequest(['email' => 'a@example.com', 'name' => 'First']));
        self::assertSame(200, $first->getStatusCode());

        $second = $this->kernel->handle($this->jsonRequest(['email' => 'b@example.com', 'name' => 'Second']));
        $payload = json_decode((string) $second->getContent(), true);
        self::assertSame('b@example.com', $payload['email']);
        self::assertSame('Second', $payload['name']);
    }

    public function testValidationExceptionPropagatesWhenCatchIsDisabled(): void
    {
        $this->expectException(\Nqphp\Core\Validation\ValidationException::class);

        $this->kernel->handle(
            $this->jsonRequest(['email' => 'nope', 'name' => 'x']),
            HttpKernelInterface::MAIN_REQUEST,
            false,
        );
    }

    public function testValidateOutsideKernelDispatchThrows(): void
    {
        $controller = new WidgetValidationController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('kernel reference is not set');

        $controller->create();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(array $payload): Request
    {
        return Request::create(
            '/_test/widgets',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    private function addRoute(Kernel $kernel, string $path, string $controller): void
    {
        $defaults = ['_controller' => $controller];
        $reflection = new \ReflectionClass($kernel->router);
        $routes = $reflection->getProperty('routes')->getValue($kernel->router);
        $routes->add('validation_test:' . md5($path), new Route($path, $defaults));
    }
}
