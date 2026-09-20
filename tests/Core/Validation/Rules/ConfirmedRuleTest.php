<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\ConfirmedRule;
use PHPUnit\Framework\TestCase;

class ConfirmedRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new ConfirmedRule([]);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testDefaultConfirmationFieldMatches(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $rule = new ConfirmedRule($data);
        $this->assertTrue($rule->passes($data['password'], 'password'));
    }

    public function testDefaultConfirmationFieldMismatches(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret456',
        ];

        $rule = new ConfirmedRule($data);
        $this->assertFalse($rule->passes($data['password'], 'password'));
    }

    public function testMissingConfirmationFieldFails(): void
    {
        $data = [
            'password' => 'secret123',
        ];

        $rule = new ConfirmedRule($data);
        $this->assertFalse($rule->passes($data['password'], 'password'));
    }

    public function testCustomConfirmationField(): void
    {
        $data = [
            'email' => 'user@example.com',
            'repeat_email' => 'user@example.com',
        ];

        $rule = new ConfirmedRule($data, 'repeat_email');
        $this->assertTrue($rule->passes($data['email'], 'email'));
    }

    public function testCustomConfirmationFieldMismatches(): void
    {
        $data = [
            'email' => 'user@example.com',
            'repeat_email' => 'other@example.com',
        ];

        $rule = new ConfirmedRule($data, 'repeat_email');
        $this->assertFalse($rule->passes($data['email'], 'email'));
    }

    public function testErrorMessage(): void
    {
        $rule = new ConfirmedRule([]);
        $this->assertSame('Поле password не совпадает с полем подтверждения.', $rule->message('password'));
    }
}
