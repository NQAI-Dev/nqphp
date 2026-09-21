<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\DeclinedIfRule;
use PHPUnit\Framework\TestCase;

class DeclinedIfRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new DeclinedIfRule('opt_out', true, []);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testPassesWhenConditionNotMetRegardlessOfValue(): void
    {
        $data = ['opt_out' => false];
        $rule = new DeclinedIfRule('opt_out', true, $data);

        $this->assertTrue($rule->passes(null, 'newsletter'));
        $this->assertTrue($rule->passes('yes', 'newsletter'));
        $this->assertTrue($rule->passes(1, 'newsletter'));
    }

    public function testPassesWhenConditionMetAndValueDeclined(): void
    {
        $data = ['gdpr_consent' => 'revoked'];
        $rule = new DeclinedIfRule('gdpr_consent', 'revoked', $data);

        $this->assertTrue($rule->passes('no', 'tracking'));
        $this->assertTrue($rule->passes('off', 'tracking'));
        $this->assertTrue($rule->passes('0', 'tracking'));
        $this->assertTrue($rule->passes(0, 'tracking'));
        $this->assertTrue($rule->passes(false, 'tracking'));
        $this->assertTrue($rule->passes('false', 'tracking'));
    }

    public function testFailsWhenConditionMetAndValueNotDeclined(): void
    {
        $data = ['gdpr_consent' => 'revoked'];
        $rule = new DeclinedIfRule('gdpr_consent', 'revoked', $data);

        $this->assertFalse($rule->passes('yes', 'tracking'));
        $this->assertFalse($rule->passes(1, 'tracking'));
        $this->assertFalse($rule->passes(true, 'tracking'));
        $this->assertFalse($rule->passes(null, 'tracking'));
        $this->assertFalse($rule->passes('', 'tracking'));
    }

    public function testMultipleConditionTargets(): void
    {
        $data = ['tier' => 'free'];
        $rule = new DeclinedIfRule('tier', ['free', 'community'], $data);

        $this->assertTrue($rule->passes('0', 'premium_support'));
        $this->assertFalse($rule->passes('1', 'premium_support'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new DeclinedIfRule('tier', 'free', []);
        $this->assertSame(
            'Поле premium_support должно быть отклонено при значении tier.',
            $rule->message('premium_support')
        );

        $custom = new DeclinedIfRule('tier', 'free', [], 'Премиум поддержка недоступна для бесплатного тарифа');
        $this->assertSame('Премиум поддержка недоступна для бесплатного тарифа', $custom->message('premium_support'));
    }
}
