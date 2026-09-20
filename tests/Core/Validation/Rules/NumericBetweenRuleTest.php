<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\NumericBetweenRule;
use PHPUnit\Framework\TestCase;

class NumericBetweenRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new NumericBetweenRule(1, 10);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonNumeric(): void
    {
        $rule = new NumericBetweenRule(1, 10);
        $this->assertFalse($rule->passes('abc', 'age'));
        $this->assertFalse($rule->passes(null, 'age'));
        $this->assertFalse($rule->passes([], 'age'));
    }

    public function testValidatesIntegerWithinRange(): void
    {
        $rule = new NumericBetweenRule(10, 20);
        $this->assertTrue($rule->passes(10, 'score'));
        $this->assertTrue($rule->passes(15, 'score'));
        $this->assertTrue($rule->passes(20, 'score'));
        $this->assertTrue($rule->passes('15', 'score'));
        $this->assertFalse($rule->passes(9, 'score'));
        $this->assertFalse($rule->passes(21, 'score'));
    }

    public function testValidatesFloatWithinRange(): void
    {
        $rule = new NumericBetweenRule(1.5, 5.5);
        $this->assertTrue($rule->passes(1.5, 'weight'));
        $this->assertTrue($rule->passes(3.14, 'weight'));
        $this->assertTrue($rule->passes('4.8', 'weight'));
        $this->assertFalse($rule->passes(1.49, 'weight'));
        $this->assertFalse($rule->passes(5.51, 'weight'));
    }

    public function testMessage(): void
    {
        $rule = new NumericBetweenRule(5, 15);
        $this->assertSame('Значение поля rating должно быть в диапазоне от 5 до 15.', $rule->message('rating'));
    }
}
