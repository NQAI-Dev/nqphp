<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\SizeRule;
use PHPUnit\Framework\TestCase;

class SizeRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new SizeRule(5);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsUnsupportedTypes(): void
    {
        $rule = new SizeRule(5);
        $this->assertFalse($rule->passes(null, 'field'));
        $this->assertFalse($rule->passes(new \stdClass(), 'field'));
    }

    public function testValidatesStringLength(): void
    {
        $rule = new SizeRule(5);
        $this->assertTrue($rule->passes('hello', 'code'));
        $this->assertTrue($rule->passes('привет', 'code') === false); // 6 chars
        $ruleRu = new SizeRule(6);
        $this->assertTrue($ruleRu->passes('привет', 'code'));
        $this->assertFalse($rule->passes('hi', 'code'));
    }

    public function testValidatesArrayCount(): void
    {
        $rule = new SizeRule(3);
        $this->assertTrue($rule->passes(['a', 'b', 'c'], 'items'));
        $this->assertFalse($rule->passes(['a', 'b'], 'items'));
        $this->assertFalse($rule->passes([], 'items'));
    }

    public function testValidatesNumericValue(): void
    {
        $rule = new SizeRule(10);
        $this->assertTrue($rule->passes(10, 'qty'));
        $this->assertTrue($rule->passes(10.0, 'qty'));
        $this->assertFalse($rule->passes(9, 'qty'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new SizeRule(5);
        $this->assertSame('Поле code должно иметь размер/значение 5.', $rule->message('code'));

        $custom = new SizeRule(5, 'Неверный размер');
        $this->assertSame('Неверный размер', $custom->message('code'));
    }
}
