<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\EmailRule;
use PHPUnit\Framework\TestCase;

class EmailRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new EmailRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrEmpty(): void
    {
        $rule = new EmailRule();
        $this->assertFalse($rule->passes(null, 'email'));
        $this->assertFalse($rule->passes([], 'email'));
        $this->assertFalse($rule->passes(12345, 'email'));
        $this->assertFalse($rule->passes('', 'email'));
        $this->assertFalse($rule->passes('   ', 'email'));
    }

    public function testAcceptsValidEmails(): void
    {
        $rule = new EmailRule();
        $this->assertTrue($rule->passes('user@example.com', 'email'));
        $this->assertTrue($rule->passes('user.name+tag@sub.domain.org', 'email'));
        $this->assertTrue($rule->passes('developer@nqphp.dev', 'email'));
    }

    public function testRejectsInvalidEmails(): void
    {
        $rule = new EmailRule();
        $this->assertFalse($rule->passes('not-an-email', 'email'));
        $this->assertFalse($rule->passes('user@', 'email'));
        $this->assertFalse($rule->passes('@example.com', 'email'));
        $this->assertFalse($rule->passes('user@domain..com', 'email'));
        $this->assertFalse($rule->passes('user name@example.com', 'email'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new EmailRule();
        $this->assertSame('Поле email должно содержать корректный email-адрес.', $rule->message('email'));

        $custom = new EmailRule('Неверный адрес почты');
        $this->assertSame('Неверный адрес почты', $custom->message('email'));
    }
}
