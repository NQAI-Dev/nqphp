<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\DeclinedRule;
use PHPUnit\Framework\TestCase;

class DeclinedRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new DeclinedRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testPassesForValidDeclinedValues(): void
    {
        $rule = new DeclinedRule();

        $this->assertTrue($rule->passes('no', 'opt_out'));
        $this->assertTrue($rule->passes('off', 'opt_out'));
        $this->assertTrue($rule->passes('0', 'opt_out'));
        $this->assertTrue($rule->passes(0, 'opt_out'));
        $this->assertTrue($rule->passes(false, 'opt_out'));
        $this->assertTrue($rule->passes('false', 'opt_out'));
    }

    public function testFailsForAcceptedOrNonDeclinedValues(): void
    {
        $rule = new DeclinedRule();

        $this->assertFalse($rule->passes('yes', 'opt_out'));
        $this->assertFalse($rule->passes('on', 'opt_out'));
        $this->assertFalse($rule->passes('1', 'opt_out'));
        $this->assertFalse($rule->passes(1, 'opt_out'));
        $this->assertFalse($rule->passes(true, 'opt_out'));
        $this->assertFalse($rule->passes('true', 'opt_out'));
        $this->assertFalse($rule->passes(null, 'opt_out'));
        $this->assertFalse($rule->passes('', 'opt_out'));
        $this->assertFalse($rule->passes([], 'opt_out'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new DeclinedRule();
        $this->assertSame(
            'Поле newsletter должно быть отклонено (нет, off, 0 или false).',
            $rule->message('newsletter')
        );

        $custom = new DeclinedRule('Вы должны отказаться от рассылки.');
        $this->assertSame('Вы должны отказаться от рассылки.', $custom->message('newsletter'));
    }
}
