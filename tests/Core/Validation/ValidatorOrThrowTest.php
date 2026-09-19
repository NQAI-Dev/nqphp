<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation;

use Nqphp\Core\Attribute\Assert;
use Nqphp\Core\Validation\ValidationException;
use Nqphp\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class TestUserDto
{
    #[Assert('required')]
    #[Assert('min', options: 3)]
    public string $username;

    #[Assert('required')]
    #[Assert('email')]
    public string $email;
}

class ValidatorOrThrowTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidateOrThrowReturnsArrayOnPass(): void
    {
        $data = ['email' => 'test@example.com', 'age' => 25];
        $rules = ['email' => 'required|email', 'age' => 'required|integer'];

        $result = $this->validator->validateOrThrow($data, $rules);
        $this->assertSame($data, $result);
    }

    public function testValidateOrThrowThrowsValidationExceptionOnArrayFail(): void
    {
        $data = ['email' => 'invalid-email', 'age' => 'abc'];
        $rules = ['email' => 'required|email', 'age' => 'required|integer'];

        try {
            $this->validator->validateOrThrow($data, $rules);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('age', $errors);
        }
    }

    public function testValidateOrThrowReturnsDtoOnPass(): void
    {
        $dto = new TestUserDto();
        $dto->username = 'alice';
        $dto->email = 'alice@example.com';

        $result = $this->validator->validateOrThrow($dto);
        $this->assertSame($dto, $result);
    }

    public function testValidateOrThrowThrowsValidationExceptionOnDtoFail(): void
    {
        $dto = new TestUserDto();
        $dto->username = 'al';
        $dto->email = 'not-an-email';

        try {
            $this->validator->validateOrThrow($dto);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $errors = $e->getErrors();
            $this->assertArrayHasKey('username', $errors);
            $this->assertArrayHasKey('email', $errors);
        }
    }
}
