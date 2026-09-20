<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation;

use Nqphp\Core\Validation\ValidationResult;
use Nqphp\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testValidResultState(): void
    {
        $validator = new Validator();
        $result = $validator->inspect(['name' => 'John', 'age' => '25'], [
            'name' => 'required|min:2',
            'age' => 'required|integer',
        ]);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->isInvalid());
        $this->assertEmpty($result->errors());
        $this->assertFalse($result->hasError('name'));
        $this->assertSame([], $result->getErrors('name'));
        $this->assertNull($result->firstError());
        $this->assertNull($result->firstError('name'));
        $this->assertSame(['name' => 'John', 'age' => '25'], $result->validated());
    }

    public function testInvalidResultStateAndHelpers(): void
    {
        $validator = new Validator();
        $result = $validator->inspect(['email' => 'invalid-email', 'age' => '15'], [
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'min:18',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isInvalid());

        $this->assertTrue($result->hasError('name'));
        $this->assertTrue($result->hasError('email'));
        $this->assertTrue($result->hasError('age'));

        $this->assertSame('The name field is required.', $result->firstError());
        $this->assertSame('The email field must be a valid email address.', $result->firstError('email'));
        $this->assertCount(1, $result->getErrors('name'));
    }
}
