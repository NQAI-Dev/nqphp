<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\AcceptedIfRule;
use PHPUnit\Framework\TestCase;

class AcceptedIfRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new AcceptedIfRule('is_commercial', true, []);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testPassesWhenConditionNotMetRegardlessOfValue(): void
    {
        $data = ['is_commercial' => false];
        $rule = new AcceptedIfRule('is_commercial', true, $data);

        $this->assertTrue($rule->passes(null, 'terms'));
        $this->assertTrue($rule->passes('no', 'terms'));
        $this->assertTrue($rule->passes(0, 'terms'));
    }

    public function testPassesWhenConditionMetAndValueAccepted(): void
    {
        $data = ['type' => 'business'];
        $rule = new AcceptedIfRule('type', 'business', $data);

        $this->assertTrue($rule->passes('yes', 'nda'));
        $this->assertTrue($rule->passes('on', 'nda'));
        $this->assertTrue($rule->passes('1', 'nda'));
        $this->assertTrue($rule->passes(1, 'nda'));
        $this->assertTrue($rule->passes(true, 'nda'));
        $this->assertTrue($rule->passes('true', 'nda'));
    }

    public function testFailsWhenConditionMetAndValueNotAccepted(): void
    {
        $data = ['type' => 'business'];
        $rule = new AcceptedIfRule('type', 'business', $data);

        $this->assertFalse($rule->passes('no', 'nda'));
        $this->assertFalse($rule->passes(0, 'nda'));
        $this->assertFalse($rule->passes(false, 'nda'));
        $this->assertFalse($rule->passes(null, 'nda'));
        $this->assertFalse($rule->passes('', 'nda'));
    }

    public function testMultipleConditionTargets(): void
    {
        $data = ['plan' => 'enterprise'];
        $rule = new AcceptedIfRule('plan', ['pro', 'enterprise'], $data);

        $this->assertTrue($rule->passes('1', 'sla_agreement'));
        $this->assertFalse($rule->passes('0', 'sla_agreement'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new AcceptedIfRule('plan', 'enterprise', []);
        $this->assertSame(
            'Поле agreement должно быть принято при значении plan.',
            $rule->message('agreement')
        );

        $custom = new AcceptedIfRule('plan', 'enterprise', [], 'Для enterprise обязательно принятие SLA');
        $this->assertSame('Для enterprise обязательно принятие SLA', $custom->message('agreement'));
    }
}
