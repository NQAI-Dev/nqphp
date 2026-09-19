<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Input;

use Nqphp\Core\Attribute\Input;
use Nqphp\Core\Input\RequestData;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

#[Input]
class SampleLoginInput
{
    public string $username;
    public string $userEmail;
    public ?string $rememberMe = null;
}

class UnmarkedInput
{
    public string $foo;
}

class RequestDataTest extends TestCase
{
    public function testExtractFromJsonBody(): void
    {
        $request = Request::create(
            '/login',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'alice', 'user_email' => 'alice@example.com'])
        );

        $extractor = new RequestData($request);
        /** @var SampleLoginInput $dto */
        $dto = $extractor->extract(SampleLoginInput::class);

        $this->assertSame('alice', $dto->username);
        $this->assertSame('alice@example.com', $dto->userEmail);
        $this->assertNull($dto->rememberMe);
    }

    public function testExtractFromFormFields(): void
    {
        $request = Request::create(
            '/login',
            'POST',
            ['username' => 'bob', 'user_email' => 'bob@example.com', 'remember_me' => '1']
        );

        $extractor = new RequestData($request);
        /** @var SampleLoginInput $dto */
        $dto = $extractor->extract(SampleLoginInput::class);

        $this->assertSame('bob', $dto->username);
        $this->assertSame('bob@example.com', $dto->userEmail);
        $this->assertSame('1', $dto->rememberMe);
    }

    public function testExtractFromQueryParams(): void
    {
        $request = Request::create('/search?username=carol&user_email=carol@example.com');

        $extractor = new RequestData($request);
        /** @var SampleLoginInput $dto */
        $dto = $extractor->extract(SampleLoginInput::class);

        $this->assertSame('carol', $dto->username);
        $this->assertSame('carol@example.com', $dto->userEmail);
    }

    public function testJsonOverridesFormAndQuery(): void
    {
        $request = Request::create(
            '/endpoint?username=query_user&user_email=query@example.com',
            'POST',
            ['username' => 'form_user'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'json_user'])
        );

        $extractor = new RequestData($request);
        /** @var SampleLoginInput $dto */
        $dto = $extractor->extract(SampleLoginInput::class);

        $this->assertSame('json_user', $dto->username);
        $this->assertSame('query@example.com', $dto->userEmail);
    }

    public function testExtractThrowsIfMissingInputAttribute(): void
    {
        $request = Request::create('/test');
        $extractor = new RequestData($request);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not marked with #[Input]');

        $extractor->extract(UnmarkedInput::class);
    }

    public function testBindSwitchesTargetRequest(): void
    {
        $initialRequest = Request::create('/login', 'POST', ['username' => 'initial']);
        $extractor = new RequestData($initialRequest);

        $newRequest = Request::create('/login', 'POST', ['username' => 'rebound']);
        $extractor->bind($newRequest);

        /** @var SampleLoginInput $dto */
        $dto = $extractor->extract(SampleLoginInput::class);
        $this->assertSame('rebound', $dto->username);
    }
}
