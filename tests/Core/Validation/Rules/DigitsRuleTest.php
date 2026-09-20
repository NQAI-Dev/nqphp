<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\DigitsRule;
use PHPUnit\Framework\TestCase;

class DigitsRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new DigitsRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonNumericStringsOrNegativeNumbers(): void
    {
        $rule = new DigitsRule();
        $this->assertFalse($rule->passes(null, 'code'));
        $this->assertFalse($rule->passes([], 'code'));
        $this->assertFalse($rule->passes('', 'code'));
        $this->assertFalse($rule->passes('abc', 'code'));
        $this->assertFalse($rule->passes('12a34', 'code'));
        $this->assertFalse($rule->passes('-123', 'code'));
    }

    public function testAcceptsDigitsWithoutConstraints(): void
    {
        $rule = new DigitsRule();
        $this->assertTrue($rule->passes('12345', 'code'));
        $this->assertTrue($rule->passes('00123', 'code'));
        $this->assertTrue($rule->passes(12345, 'code'));
        $this->assertSame('Поле code должно содержать только цифры.', $rule->message('code'));
    }

    public function testExactLength(): void
    {
        $rule = new DigitsRule(length: 6);
        $this->assertTrue($rule->passes('123456', 'sms_code'));
        $this->assertFalse($rule->passes('12345', 'sms_code'));
        $this->assertFalse($rule->passes('1234567', 'sms_code'));
        $this->assertSame('Поле sms_code должно состоять ровно из 6 цифр.', $rule->message('sms_code'));
    }

    public function testBoundedLength(): void
    {
        $rule = new DigitsRule(min: 3, max: 5);
        $this->assertFalse($rule->passes('12', 'pin'));
        $this->assertTrue($rule->passes('123', 'pin'));
        $this->assertTrue($rule->passes('1234', 'pin'));
        $this->assertTrue($rule->passes('12345', 'pin'));
        $this->assertFalse($rule->passes('123456', 'pin'));
        $this->assertSame('Поле pin должно содержать от 3 до 5 цифр.', $rule->message('pin'));
    }

    public function testCustomMessage(): void
    {
        $rule = new DigitsRule(length: 4, customMessage: 'Неверный PIN-код');
        $this->assertSame('Неверный PIN-код', $rule->message('pin'));
    }
}
