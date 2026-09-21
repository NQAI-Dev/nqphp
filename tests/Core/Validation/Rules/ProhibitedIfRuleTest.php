<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\ProhibitedIfRule;
use PHPUnit\Framework\TestCase;

class ProhibitedIfRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new ProhibitedIfRule('status', 'draft', []);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testPassesWhenConditionNotMet(): void
    {
        $data = ['role' => 'admin'];
        $rule = new ProhibitedIfRule('role', 'guest', $data);

        $this->assertTrue($rule->passes('anything', 'field'));
    }

    public function testPassesWhenConditionMetAndValueEmpty(): void
    {
        $data = ['role' => 'guest'];
        $rule = new ProhibitedIfRule('role', 'guest', $data);

        $this->assertTrue($rule->passes(null, 'permissions'));
        $this->assertTrue($rule->passes('', 'permissions'));
        $this->assertTrue($rule->passes('   ', 'permissions'));
        $this->assertTrue($rule->passes([], 'permissions'));
    }

    public function testFailsWhenConditionMetAndValueProvided(): void
    {
        $data = ['role' => 'guest'];
        $rule = new ProhibitedIfRule('role', 'guest', $data);

        $this->assertFalse($rule->passes('write', 'permissions'));
        $this->assertFalse($rule->passes(0, 'permissions'));
        $this->assertFalse($rule->passes(false, 'permissions'));
        $this->assertFalse($rule->passes(['write'], 'permissions'));
    }

    public function testMultipleTargetValues(): void
    {
        $data = ['type' => 'digital'];
        $rule = new ProhibitedIfRule('type', ['digital', 'virtual'], $data);

        $this->assertFalse($rule->passes('123 Elm St', 'shipping_address'));
        $this->assertTrue($rule->passes(null, 'shipping_address'));

        $dataPhysical = ['type' => 'physical'];
        $rulePhysical = new ProhibitedIfRule('type', ['digital', 'virtual'], $dataPhysical);
        $this->assertTrue($rulePhysical->passes('123 Elm St', 'shipping_address'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new ProhibitedIfRule('type', 'digital', []);
        $this->assertSame(
            'Поле shipping_address запрещено для заполнения при значении type.',
            $rule->message('shipping_address')
        );

        $custom = new ProhibitedIfRule('type', 'digital', [], 'Адрес не требуется для цифровых товаров');
        $this->assertSame('Адрес не требуется для цифровых товаров', $custom->message('shipping_address'));
    }
}
