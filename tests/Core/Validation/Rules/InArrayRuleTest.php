<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\InArrayRule;
use PHPUnit\Framework\TestCase;

class InArrayRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new InArrayRule(['a', 'b']);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testNonStrictComparison(): void
    {
        $rule = new InArrayRule(['1', '2', '3']);
        $this->assertTrue($rule->passes('1', 'status'));
        $this->assertTrue($rule->passes(1, 'status'));
        $this->assertFalse($rule->passes(4, 'status'));
    }

    public function testStrictComparison(): void
    {
        $rule = new InArrayRule([1, 2, 3], strict: true);
        $this->assertTrue($rule->passes(1, 'status'));
        $this->assertFalse($rule->passes('1', 'status'));
        $this->assertFalse($rule->passes('other', 'status'));
    }

    public function testMessageFormatting(): void
    {
        $rule = new InArrayRule(['pending', 'active', 'banned']);
        $this->assertSame(
            'Значение поля status должно быть одним из: pending, active, banned.',
            $rule->message('status')
        );
    }
}
