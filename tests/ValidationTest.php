<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\Attribute\Assert;
use Nqphp\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class TestDto
{
    #[Assert('required')]
    #[Assert('email')]
    public string $email;

    #[Assert('required')]
    #[Assert('min', options: 3)]
    public string $name;

    #[Assert('max', options: 10)]
    public int $age = 5;
}

final class ValidationTest extends TestCase
{
    public function testValidObject(): void
    {
        $validator = new Validator();
        $dto = new TestDto();
        $dto->email = 'test@example.com';
        $dto->name = 'Alice';
        $dto->age = 10;

        $errors = $validator->validate($dto);
        $this->assertEmpty($errors);
    }

    public function testInvalidObject(): void
    {
        $validator = new Validator();
        $dto = new TestDto();
        $dto->email = 'invalid-email';
        $dto->name = 'Al';
        $dto->age = 15;

        $errors = $validator->validate($dto);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Invalid email format', $errors['email'][0]);

        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('Minimum value/length is 3', $errors['name'][0]);

        $this->assertArrayHasKey('age', $errors);
        $this->assertEquals('Maximum value/length is 10', $errors['age'][0]);
    }

    public function testRequiredObject(): void
    {
        $validator = new Validator();
        $dto = new TestDto(); // uninitialized required fields

        $errors = $validator->validate($dto);

        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('name', $errors);
    }
}
