<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\NotInArrayRule;
use PHPUnit\Framework\TestCase;

class NotInArrayRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new NotInArrayRule(['root', 'admin']);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testNonStrictComparison(): void
    {
        $rule = new NotInArrayRule(['0', 'admin']);
        $this->assertFalse($rule->passes(0, 'username'));
        $this->assertFalse($rule->passes('admin', 'username'));
        $this->assertTrue($rule->passes('user', 'username'));
        $this->assertTrue($rule->passes(1, 'username'));
    }

    public function testStrictComparison(): void
    {
        $rule = new NotInArrayRule([0, 'admin'], strict: true);
        $this->assertFalse($rule->passes(0, 'username'));
        $this->assertFalse($rule->passes('admin', 'username'));
        $this->assertTrue($rule->passes('0', 'username'));
        $this->assertTrue($rule->passes('guest', 'username'));
    }

    public function testMessageFormatting(): void
    {
        $rule = new NotInArrayRule(['root', 'admin']);
        $this->assertSame(
            'Поле username не должно содержать любое из следующих значений: root, admin.',
            $rule->message('username')
        );
    }
}
