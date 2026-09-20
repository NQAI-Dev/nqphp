<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\RegexRule;
use PHPUnit\Framework\TestCase;

class RegexRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new RegexRule('/^[a-z]+$/');
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringAndNonNumeric(): void
    {
        $rule = new RegexRule('/^[a-z]+$/');
        $this->assertFalse($rule->passes(null, 'code'));
        $this->assertFalse($rule->passes([], 'code'));
        $this->assertFalse($rule->passes(new \stdClass(), 'code'));
    }

    public function testMatchesPattern(): void
    {
        $rule = new RegexRule('/^[0-9]{4}$/');
        $this->assertTrue($rule->passes('1234', 'pin'));
        $this->assertTrue($rule->passes(1234, 'pin'));
        $this->assertFalse($rule->passes('123', 'pin'));
        $this->assertFalse($rule->passes('12345', 'pin'));
        $this->assertFalse($rule->passes('abcd', 'pin'));
    }

    public function testDefaultMessage(): void
    {
        $rule = new RegexRule('/^[a-z]+$/');
        $this->assertSame('Поле slug имеет недопустимый формат.', $rule->message('slug'));
    }

    public function testCustomMessage(): void
    {
        $rule = new RegexRule('/^[0-9]+$/', 'Должно содержать только цифры.');
        $this->assertSame('Должно содержать только цифры.', $rule->message('code'));
    }
}
