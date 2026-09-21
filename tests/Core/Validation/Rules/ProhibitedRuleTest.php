<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\ProhibitedRule;
use PHPUnit\Framework\TestCase;

class ProhibitedRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new ProhibitedRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testPassesWhenValueIsEmptyOrNull(): void
    {
        $rule = new ProhibitedRule();
        $this->assertTrue($rule->passes(null, 'honeypot'));
        $this->assertTrue($rule->passes('', 'honeypot'));
        $this->assertTrue($rule->passes('   ', 'honeypot'));
        $this->assertTrue($rule->passes([], 'honeypot'));
    }

    public function testFailsWhenValueIsNotEmpty(): void
    {
        $rule = new ProhibitedRule();
        $this->assertFalse($rule->passes('spam', 'honeypot'));
        $this->assertFalse($rule->passes(0, 'honeypot'));
        $this->assertFalse($rule->passes(false, 'honeypot'));
        $this->assertFalse($rule->passes(['key' => 'val'], 'honeypot'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new ProhibitedRule();
        $this->assertSame('Поле honeypot запрещено для заполнения.', $rule->message('honeypot'));

        $custom = new ProhibitedRule('Это поле должно оставаться пустым.');
        $this->assertSame('Это поле должно оставаться пустым.', $custom->message('honeypot'));
    }
}
