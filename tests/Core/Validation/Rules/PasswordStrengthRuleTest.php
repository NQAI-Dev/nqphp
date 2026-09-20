<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\PasswordStrengthRule;
use PHPUnit\Framework\TestCase;

class PasswordStrengthRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new PasswordStrengthRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonString(): void
    {
        $rule = new PasswordStrengthRule();
        $this->assertFalse($rule->passes(12345678, 'password'));
        $this->assertFalse($rule->passes(null, 'password'));
    }

    public function testRejectsShortPassword(): void
    {
        $rule = new PasswordStrengthRule(minLength: 8);
        $this->assertFalse($rule->passes('Aa1!bcd', 'password'));
    }

    public function testValidatesMixedCase(): void
    {
        $rule = new PasswordStrengthRule(requireMixedCase: true, requireDigits: false, requireSpecialChars: false);
        $this->assertFalse($rule->passes('abcdefgh', 'password'));
        $this->assertFalse($rule->passes('ABCDEFGH', 'password'));
        $this->assertTrue($rule->passes('Abcdefgh', 'password'));
    }

    public function testValidatesDigits(): void
    {
        $rule = new PasswordStrengthRule(requireMixedCase: false, requireDigits: true, requireSpecialChars: false);
        $this->assertFalse($rule->passes('abcdefgh', 'password'));
        $this->assertTrue($rule->passes('abcdefg1', 'password'));
    }

    public function testValidatesSpecialChars(): void
    {
        $rule = new PasswordStrengthRule(requireMixedCase: false, requireDigits: false, requireSpecialChars: true);
        $this->assertFalse($rule->passes('abcdefgh1', 'password'));
        $this->assertTrue($rule->passes('abcdefgh@', 'password'));
    }

    public function testStrongPasswordPassesAllDefaults(): void
    {
        $rule = new PasswordStrengthRule();
        $this->assertTrue($rule->passes('Secret123!', 'password'));
    }

    public function testErrorMessage(): void
    {
        $rule = new PasswordStrengthRule();
        $this->assertSame('Поле password не соответствует требованиям надежности пароля.', $rule->message('password'));
    }
}
